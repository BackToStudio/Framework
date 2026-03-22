<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Infrastructure;

use BackTo\Framework\Observability\HealthCheck\DatabaseConnectionInterface;

/**
 * WordPress adapter for database connectivity checks using $wpdb.
 */
final class WordPressDatabaseConnection implements DatabaseConnectionInterface
{
    public function query(string $sql): ?string
    {
        global $wpdb;

        $result = $wpdb->get_var($sql);

        return $result !== null ? (string) $result : null;
    }

    public function getTableCount(): int
    {
        global $wpdb;

        $tables = $wpdb->get_results(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $wpdb->esc_like($wpdb->prefix) . '%',
            ),
        );

        return \count($tables);
    }

    public function getServerInfo(): string
    {
        global $wpdb;

        return $wpdb->db_server_info() ?: 'unknown';
    }
}
