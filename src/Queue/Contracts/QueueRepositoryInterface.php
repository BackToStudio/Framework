<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Contracts;

/**
 * Composite port interface for queue storage.
 *
 * Extends all segregated queue interfaces for backward compatibility.
 * Prefer depending on the narrowest interface your class actually needs:
 * - QueueJobStorageInterface: enqueue, claim, lifecycle transitions
 * - QueueQueryInterface: read-only queries (findByStatus, countByStatus, getActiveGroups)
 * - QueueMaintenanceInterface: cleanup and rescue operations
 * - QueueSchemaInterface: table creation/deletion
 */
interface QueueRepositoryInterface extends
    QueueJobStorageInterface,
    QueueQueryInterface,
    QueueMaintenanceInterface,
    QueueSchemaInterface
{
}
