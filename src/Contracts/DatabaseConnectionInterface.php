<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Port interface for database access.
 *
 * Abstracts the WordPress $wpdb global so that infrastructure classes
 * can be tested without a live database connection.
 */
interface DatabaseConnectionInterface
{
    /**
     * Execute a raw SQL query.
     *
     * @return int|bool Number of rows affected/selected, or false on error.
     */
    public function query(string $sql): int|bool;

    /**
     * Insert a row into a table.
     *
     * @param string $table Table name.
     * @param array<string, mixed> $data Column => value pairs.
     * @param string[]|string $format Format specifiers (%s, %d, %f).
     * @return int|false The number of rows inserted, or false on error.
     */
    public function insert(string $table, array $data, array|string $format = ''): int|false;

    /**
     * Update rows in a table.
     *
     * @param string $table Table name.
     * @param array<string, mixed> $data Column => value pairs to update.
     * @param array<string, mixed> $where WHERE column => value pairs.
     * @param string[]|string $format Format specifiers for $data.
     * @param string[]|string $whereFormat Format specifiers for $where.
     * @return int|false The number of rows updated, or false on error.
     */
    public function update(string $table, array $data, array $where, array|string $format = '', array|string $whereFormat = ''): int|false;

    /**
     * Retrieve a single row as an associative array.
     *
     * @return array<string, mixed>|null
     */
    public function getRow(string $sql): ?array;

    /**
     * Retrieve multiple rows as arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getResults(string $sql): array;

    /**
     * Retrieve a single column value.
     */
    public function getVar(string $sql): ?string;

    /**
     * Retrieve a single column from multiple rows.
     *
     * @return string[]
     */
    public function getCol(string $sql): array;

    /**
     * Prepare a SQL query with placeholders.
     *
     * @param mixed ...$args Values to substitute for placeholders.
     */
    public function prepare(string $query, mixed ...$args): string;

    /**
     * Escape a string for use in a LIKE clause.
     */
    public function escLike(string $text): string;

    /**
     * Get the last auto-increment ID from an insert.
     */
    public function lastInsertId(): int;

    /**
     * Get the table prefix.
     */
    public function prefix(): string;

    /**
     * Get the charset collation string for CREATE TABLE.
     */
    public function charsetCollate(): string;
}
