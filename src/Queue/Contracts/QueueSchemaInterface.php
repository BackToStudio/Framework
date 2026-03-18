<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Contracts;

/**
 * Schema management for queue storage.
 */
interface QueueSchemaInterface
{
    /**
     * Create the storage table if it does not exist.
     */
    public function createTable(): void;

    /**
     * Drop the storage table.
     */
    public function dropTable(): void;
}
