<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Infrastructure;

use BackTo\Framework\Security\Contracts\AuditLogRepositoryInterface;

use function get_option;
use function update_option;

/**
 * WordPress adapter for audit log persistence using options API.
 *
 * Stores events as a serialized array in wp_options.
 * For high-volume sites, consider replacing with a custom table adapter.
 */
final class WordPressAuditLogRepository implements AuditLogRepositoryInterface
{
    private const OPTION_KEY = 'backto_security_audit_log';
    private const MAX_STORED_EVENTS = 1000;

    /** @var array<int, array<string, mixed>>|null In-memory cache to avoid repeated deserialization */
    private ?array $cachedEvents = null;

    /**
     * @param array<string, mixed> $context
     */
    public function store(string $event, string $severity, array $context): void
    {
        // Always re-read from database to reduce the race condition window.
        // Two concurrent writes can still lose events, but the stale in-memory
        // cache was making this much worse.
        $this->cachedEvents = null;
        $events = $this->loadEvents();

        $events[] = [
            'event' => $event,
            'severity' => $severity,
            'context' => $context,
            'timestamp' => time(),
        ];

        // Keep only the most recent events
        if (count($events) > self::MAX_STORED_EVENTS) {
            $events = array_slice($events, -self::MAX_STORED_EVENTS);
        }

        update_option(self::OPTION_KEY, $events, false);
        $this->cachedEvents = $events;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getEvents(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $events = $this->loadEvents();

        // Apply filters
        if (isset($filters['event'])) {
            $events = array_filter($events, fn (array $e) => $e['event'] === $filters['event']);
        }

        if (isset($filters['severity'])) {
            $events = array_filter($events, fn (array $e) => $e['severity'] === $filters['severity']);
        }

        if (isset($filters['user_id'])) {
            $events = array_filter($events, fn (array $e) => ($e['context']['user_id'] ?? null) === $filters['user_id']);
        }

        // Sort by timestamp descending (most recent first)
        usort($events, fn (array $a, array $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($events, $offset, $limit);
    }

    public function purge(int $olderThanDays): int
    {
        $events = $this->loadEvents();
        $cutoff = time() - ($olderThanDays * 86400);
        $originalCount = count($events);

        $events = array_filter($events, fn (array $e) => $e['timestamp'] >= $cutoff);

        update_option(self::OPTION_KEY, $events, false);
        $this->cachedEvents = array_values($events);

        return $originalCount - count($events);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadEvents(): array
    {
        if ($this->cachedEvents !== null) {
            return $this->cachedEvents;
        }

        $events = get_option(self::OPTION_KEY, []);
        $this->cachedEvents = is_array($events) ? $events : [];

        return $this->cachedEvents;
    }
}
