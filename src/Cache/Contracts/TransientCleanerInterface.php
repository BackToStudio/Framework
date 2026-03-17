<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Contracts;

/**
 * Port interface for bulk-clearing transients from the database.
 */
interface TransientCleanerInterface
{
    /**
     * Delete all transients matching the given prefix.
     */
    public function clearByPrefix(string $prefix): bool;
}
