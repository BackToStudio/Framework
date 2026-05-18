<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Infrastructure;

use BackTo\Framework\Cache\Contracts\CacheStoreInterface;
use BackToVendor\Symfony\Component\Lock\Exception\LockConflictedException;
use BackToVendor\Symfony\Component\Lock\Key;
use BackToVendor\Symfony\Component\Lock\PersistingStoreInterface;

/**
 * Symfony Lock store backed by the framework's CacheStoreInterface.
 *
 * WARNING: This store is advisory-only. The check-then-set pattern is NOT
 * atomic with WordPress transients and most cache backends. Two concurrent
 * processes CAN both acquire the same lock under high concurrency.
 *
 * For production environments requiring strict mutual exclusion, use:
 * - Symfony's FlockStore (filesystem-based, single-server only)
 * - Symfony's RedisStore with SETNX (multi-server safe)
 * - A database-backed store with INSERT ... ON DUPLICATE KEY
 *
 * This store is suitable for best-effort duplicate prevention (e.g., cron
 * overlap protection) where occasional double-execution is tolerable.
 */
final class CacheStoreLockStore implements PersistingStoreInterface
{
    private const KEY_PREFIX = 'backto_lock_';

    private readonly CacheStoreInterface $cacheStore;
    private readonly int $defaultTtl;

    public function __construct(CacheStoreInterface $cacheStore, int $defaultTtl = 300)
    {
        if ($defaultTtl < 1) {
            throw new \InvalidArgumentException(\sprintf('Lock store default TTL must be at least 1 second, got %d.', $defaultTtl));
        }

        $this->cacheStore = $cacheStore;
        $this->defaultTtl = $defaultTtl;
    }

    public function save(Key $key): void
    {
        $resource = (string) $key;
        $cacheKey = self::KEY_PREFIX . $resource;
        $token = $this->getUniqueToken($key);
        $existing = $this->cacheStore->get($cacheKey);

        if ($existing !== false && $existing !== null && $existing !== $token) {
            throw new LockConflictedException();
        }

        $this->cacheStore->set($cacheKey, $token, $this->defaultTtl);

        // Double-check: re-read to narrow the TOCTOU window.
        $verification = $this->cacheStore->get($cacheKey);
        if ($verification !== $token) {
            throw new LockConflictedException();
        }
    }

    public function delete(Key $key): void
    {
        $resource = (string) $key;
        $cacheKey = self::KEY_PREFIX . $resource;
        $token = $this->getUniqueToken($key);
        $existing = $this->cacheStore->get($cacheKey);

        if ($existing === $token) {
            $this->cacheStore->delete($cacheKey);
        }
    }

    public function exists(Key $key): bool
    {
        $resource = (string) $key;
        $cacheKey = self::KEY_PREFIX . $resource;
        $token = $this->getUniqueToken($key);
        $existing = $this->cacheStore->get($cacheKey);

        return $existing === $token;
    }

    public function putOffExpiration(Key $key, float $ttl): void
    {
        $resource = (string) $key;
        $cacheKey = self::KEY_PREFIX . $resource;
        $token = $this->getUniqueToken($key);
        $existing = $this->cacheStore->get($cacheKey);

        if ($existing !== $token) {
            throw new LockConflictedException();
        }

        $this->cacheStore->set($cacheKey, $token, (int) ceil($ttl));
        $key->reduceLifetime($ttl);
    }

    private function getUniqueToken(Key $key): string
    {
        if (!$key->hasState(__CLASS__)) {
            $key->setState(__CLASS__, bin2hex(random_bytes(16)));
        }

        return $key->getState(__CLASS__);
    }
}
