<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Contracts;

use BackTo\Framework\Contracts\DatabaseConnectionInterface;

/**
 * A single database cleanup operation.
 *
 * Implementations are registered and executed by the DatabaseOptimizer,
 * enabling new cleanup tasks to be added without modifying the optimizer (OCP).
 */
interface CleanupStrategyInterface
{
    /**
     * Unique name identifying this cleanup strategy.
     */
    public function name(): string;

    /**
     * Execute the cleanup and return the number of rows affected.
     */
    public function execute(DatabaseConnectionInterface $db): int;
}
