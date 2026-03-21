<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\HealthCheck;

/**
 * Port interface for database connectivity checks.
 *
 * Abstracts the database access needed by DatabaseHealthCheck,
 * keeping it independent of $wpdb.
 */
interface DatabaseConnectionInterface
{
    /**
     * Execute a simple query and return the scalar result.
     */
    public function query(string $sql): ?string;

    /**
     * Return the number of tables with the configured prefix.
     */
    public function getTableCount(): int;

    /**
     * Return the database server version string.
     */
    public function getServerInfo(): string;
}
