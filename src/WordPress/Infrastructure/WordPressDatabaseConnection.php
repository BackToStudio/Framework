<?php

declare(strict_types=1);

namespace BackTo\Framework\WordPress\Infrastructure;

use BackTo\Framework\Contracts\DatabaseConnectionInterface;

/**
 * WordPress adapter for database access via $wpdb.
 */
final class WordPressDatabaseConnection implements DatabaseConnectionInterface
{
    private readonly string $prefix;

    public function __construct()
    {
        global $wpdb;
        $this->prefix = $wpdb->prefix;
    }

    public function query(string $sql): int|bool
    {
        global $wpdb;

        return $wpdb->query($sql);
    }

    public function insert(string $table, array $data, array|string $format = ''): int|false
    {
        global $wpdb;

        $result = $wpdb->insert($table, $data, $format !== '' ? $format : null);

        return $result !== false ? (int) $result : false;
    }

    public function update(string $table, array $data, array $where, array|string $format = '', array|string $whereFormat = ''): int|false
    {
        global $wpdb;

        $result = $wpdb->update(
            $table,
            $data,
            $where,
            $format !== '' ? $format : null,
            $whereFormat !== '' ? $whereFormat : null,
        );

        return $result !== false ? (int) $result : false;
    }

    public function getRow(string $sql): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row($sql, ARRAY_A);

        return is_array($row) ? $row : null;
    }

    public function getResults(string $sql): array
    {
        global $wpdb;

        $results = $wpdb->get_results($sql, ARRAY_A);

        return is_array($results) ? $results : [];
    }

    public function getVar(string $sql): ?string
    {
        global $wpdb;

        $value = $wpdb->get_var($sql);

        return $value !== null ? (string) $value : null;
    }

    public function getCol(string $sql): array
    {
        global $wpdb;

        $col = $wpdb->get_col($sql);

        return is_array($col) ? $col : [];
    }

    public function prepare(string $query, mixed ...$args): string
    {
        global $wpdb;

        return (string) $wpdb->prepare($query, ...$args);
    }

    public function escLike(string $text): string
    {
        global $wpdb;

        return $wpdb->esc_like($text);
    }

    public function lastInsertId(): int
    {
        global $wpdb;

        return (int) $wpdb->insert_id;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function charsetCollate(): string
    {
        global $wpdb;

        return $wpdb->get_charset_collate();
    }
}
