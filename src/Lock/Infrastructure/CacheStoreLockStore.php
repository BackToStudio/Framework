<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Infrastructure;

use BackTo\Framework\Cache\Contracts\CacheStoreInterface;
use BackTo\Framework\Lock\Contracts\LockStoreInterface;

/**
 * Lock store backed by the framework's CacheStoreInterface.
 *
 * Uses WordPress transients, Redis, or any cache backend
 * depending on the configured cache strategy. The TTL ensures
 * automatic expiration if the holder crashes.
 *
 * Note: check-then-set is not fully atomic with WordPress transients.
 * For truly concurrent environments, use DatabaseLockStore instead.
 */
final class CacheStoreLockStore implements LockStoreInterface
{
    private const KEY_PREFIX = 'backto_lock_';

    private readonly CacheStoreInterface $cacheStore;

    public function __construct(CacheStoreInterface $cacheStore)
    {
        $this->cacheStore = $cacheStore;
    }

    public function acquire(string $resource, string $token, int $ttl): bool
    {
        $key = self::KEY_PREFIX . $resource;
        $existing = $this->cacheStore->get($key);

        if ($existing !== false && $existing !== null) {
            // Already locked — check if we own it (re-entrant).
            if ($existing === $token) {
                return true;
            }

            return false;
        }

        $this->cacheStore->set($key, $token, $ttl);

        return true;
    }

    public function release(string $resource, string $token): void
    {
        $key = self::KEY_PREFIX . $resource;
        $existing = $this->cacheStore->get($key);

        // Only release if we own the lock.
        if ($existing === $token) {
            $this->cacheStore->delete($key);
        }
    }

    public function exists(string $resource): bool
    {
        $key = self::KEY_PREFIX . $resource;
        $value = $this->cacheStore->get($key);

        return $value !== false && $value !== null;
    }
}
