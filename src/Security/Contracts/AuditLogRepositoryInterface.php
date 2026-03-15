<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Contracts;

/**
 * Port interface for persisting security audit events.
 */
interface AuditLogRepositoryInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function store(string $event, string $severity, array $context): void;

    /**
     * @param array<string, mixed> $filters Optional filters: event, severity, user_id, date_from, date_to
     * @return array<int, array<string, mixed>>
     */
    public function getEvents(array $filters = [], int $limit = 100, int $offset = 0): array;

    /**
     * Delete events older than the given number of days.
     */
    public function purge(int $olderThanDays): int;
}
