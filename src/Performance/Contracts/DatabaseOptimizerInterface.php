<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Contracts;

/**
 * Port for WordPress database cleanup and optimization.
 */
interface DatabaseOptimizerInterface
{
    /**
     * Run all configured cleanup tasks.
     *
     * @return array<string, int> Map of task name => number of rows affected.
     */
    public function cleanup(): array;

    /**
     * Optimize database tables (OPTIMIZE TABLE).
     *
     * @return int Number of tables optimized.
     */
    public function optimizeTables(): int;
}
