<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Contracts;

/**
 * Port interface for Redis client operations.
 *
 * Abstracts the Redis client (phpredis extension, Predis, etc.)
 * so RedisCache remains testable and adapter-agnostic.
 */
interface RedisClientInterface
{
    /**
     * Get a value by key. Returns null if key does not exist.
     */
    public function get(string $key): ?string;

    /**
     * Set a value with optional TTL in seconds.
     * TTL of 0 means no expiration.
     */
    public function set(string $key, string $value, int $ttl = 0): bool;

    /**
     * Delete a key. Returns true if key existed.
     */
    public function del(string $key): bool;

    /**
     * Check if a key exists.
     */
    public function exists(string $key): bool;

    /**
     * Delete all keys matching the given prefix pattern.
     */
    public function flushByPrefix(string $prefix): bool;

    /**
     * Ping the Redis server. Returns true if reachable.
     */
    public function ping(): bool;

    /**
     * Return server info as key-value pairs.
     *
     * @return array<string, string>
     */
    public function info(): array;
}
