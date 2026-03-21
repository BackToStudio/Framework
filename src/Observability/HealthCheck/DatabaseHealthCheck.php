<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\HealthCheck;

use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckResult;

/**
 * Verifies database connectivity and query performance.
 *
 * Checks:
 * - Basic connectivity (SELECT 1)
 * - Query response time (degraded if > 100ms)
 * - WordPress tables existence
 */
final class DatabaseHealthCheck implements HealthCheckInterface
{
    /** Query time threshold in milliseconds above which the check is degraded. */
    private const SLOW_THRESHOLD_MS = 100.0;

    private readonly DatabaseConnectionInterface $connection;

    public function __construct(DatabaseConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'database';
    }

    public function check(): HealthCheckResult
    {
        try {
            $start = \hrtime(true);
            $result = $this->connection->query('SELECT 1');
            $elapsed = (\hrtime(true) - $start) / 1_000_000;

            if ($result !== '1') {
                return HealthCheckResult::unhealthy('Database query returned unexpected result', [
                    'expected' => '1',
                    'actual' => $result,
                ]);
            }

            $tableCount = $this->connection->getTableCount();

            if ($tableCount === 0) {
                return HealthCheckResult::unhealthy('No WordPress tables found');
            }

            $metadata = [
                'response_time_ms' => \round($elapsed, 2),
                'table_count' => $tableCount,
                'server_info' => $this->connection->getServerInfo(),
            ];

            if ($elapsed > self::SLOW_THRESHOLD_MS) {
                return HealthCheckResult::degraded(
                    \sprintf('Database responding slowly (%.1fms)', $elapsed),
                    $metadata,
                );
            }

            return HealthCheckResult::healthy('Database operational', $metadata);
        } catch (\Throwable $e) {
            return HealthCheckResult::unhealthy('Database connection failed: ' . $e->getMessage());
        }
    }
}
