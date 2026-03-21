<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Contracts;

/**
 * Port interface for key-value cache storage operations.
 *
 * Abstracts the underlying cache backend (transients, Redis, Memcached, etc.)
 * so that application-layer code does not depend on a specific implementation.
 */
interface CacheStoreInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $expiration = 0): bool;

    public function delete(string $key): bool;
}
