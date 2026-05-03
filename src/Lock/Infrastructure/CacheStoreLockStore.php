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
 * Uses WordPress transients, Redis, or any cache backend
 * depending on the configured cache strategy. The TTL ensures
 * automatic expiration if the holder crashes.
 *
 * Note: check-then-set is not fully atomic with WordPress transients.
 * For truly concurrent environments, use a database-backed store instead.
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
