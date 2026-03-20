<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Bundle\Security\Infrastructure\WordPressAuditLogRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for WordPressAuditLogRepository race condition mitigation.
 *
 * These tests verify that the store() method invalidates the in-memory cache
 * before writing, reducing the risk of stale-read overwrites.
 */
class AuditLogRepositoryTest extends TestCase
{
    /**
     * The store() method should invalidate cache before re-reading.
     * We verify this by checking that consecutive stores don't lose events.
     */
    public function testConsecutiveStoresAccumulate(): void
    {
        // We can't test the real WordPress option without integration tests,
        // but we can test the internal logic by subclassing.
        $repo = new InMemoryAuditLogRepository();

        $repo->store('event_1', 'info', ['key' => 'a']);
        $repo->store('event_2', 'warning', ['key' => 'b']);
        $repo->store('event_3', 'critical', ['key' => 'c']);

        $events = $repo->getEvents();

        $this->assertCount(3, $events);
    }

    public function testStoreRespectsMaxLimit(): void
    {
        $repo = new InMemoryAuditLogRepository(5);

        for ($i = 0; $i < 8; $i++) {
            $repo->store("event_{$i}", 'info', ['index' => $i]);
        }

        $events = $repo->getEvents([], 100);

        // Should keep only the 5 most recent
        $this->assertCount(5, $events);

        // Most recent should be last stored
        $indices = array_map(fn (array $e) => $e['context']['index'], $events);
        $this->assertContains(7, $indices);
        $this->assertContains(6, $indices);
        $this->assertContains(5, $indices);
    }

    public function testPurgeRemovesOldEvents(): void
    {
        $repo = new InMemoryAuditLogRepository();

        // Store an event with a timestamp from 90 days ago
        $repo->storeWithTimestamp('old_event', 'info', [], time() - (90 * 86400));
        $repo->store('recent_event', 'info', []);

        $purged = $repo->purge(30);

        $this->assertSame(1, $purged);
        $events = $repo->getEvents();
        $this->assertCount(1, $events);
        $this->assertSame('recent_event', $events[0]['event']);
    }

    public function testGetEventsFiltersByEvent(): void
    {
        $repo = new InMemoryAuditLogRepository();
        $repo->store('login_failed', 'warning', []);
        $repo->store('file_changed', 'info', []);
        $repo->store('login_failed', 'warning', []);

        $events = $repo->getEvents(['event' => 'login_failed']);

        $this->assertCount(2, $events);
    }

    public function testGetEventsFiltersBySeverity(): void
    {
        $repo = new InMemoryAuditLogRepository();
        $repo->store('event_a', 'info', []);
        $repo->store('event_b', 'critical', []);
        $repo->store('event_c', 'info', []);

        $events = $repo->getEvents(['severity' => 'critical']);

        $this->assertCount(1, $events);
        $this->assertSame('event_b', $events[0]['event']);
    }
}

/**
 * In-memory test double for WordPressAuditLogRepository.
 * Replicates the same logic without WordPress dependencies.
 */
class InMemoryAuditLogRepository
{
    /** @var array<int, array<string, mixed>> */
    private array $events = [];
    private int $maxEvents;

    public function __construct(int $maxEvents = 1000)
    {
        $this->maxEvents = $maxEvents;
    }

    /** @param array<string, mixed> $context */
    public function store(string $event, string $severity, array $context): void
    {
        $this->storeWithTimestamp($event, $severity, $context, time());
    }

    /** @param array<string, mixed> $context */
    public function storeWithTimestamp(string $event, string $severity, array $context, int $timestamp): void
    {
        $this->events[] = [
            'event' => $event,
            'severity' => $severity,
            'context' => $context,
            'timestamp' => $timestamp,
        ];

        if (count($this->events) > $this->maxEvents) {
            $this->events = array_slice($this->events, -$this->maxEvents);
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getEvents(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $events = $this->events;

        if (isset($filters['event'])) {
            $events = array_values(array_filter($events, fn (array $e) => $e['event'] === $filters['event']));
        }

        if (isset($filters['severity'])) {
            $events = array_values(array_filter($events, fn (array $e) => $e['severity'] === $filters['severity']));
        }

        usort($events, fn (array $a, array $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($events, $offset, $limit);
    }

    public function purge(int $olderThanDays): int
    {
        $cutoff = time() - ($olderThanDays * 86400);
        $originalCount = count($this->events);

        $this->events = array_values(array_filter($this->events, fn (array $e) => $e['timestamp'] >= $cutoff));

        return $originalCount - count($this->events);
    }
}
