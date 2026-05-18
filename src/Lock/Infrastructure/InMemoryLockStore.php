<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Infrastructure;

use BackToVendor\Symfony\Component\Lock\Exception\LockConflictedException;
use BackToVendor\Symfony\Component\Lock\Key;
use BackToVendor\Symfony\Component\Lock\PersistingStoreInterface;

/**
 * In-memory lock store for testing and single-process use cases.
 *
 * Implements Symfony's PersistingStoreInterface for compatibility with
 * the Symfony Lock component. Locks are held in an array and do not
 * survive across requests. TTL is tracked and checked on save/exists.
 */
final class InMemoryLockStore implements PersistingStoreInterface
{
    /** @var array<string, array{token: string, expires: float}> */
    private array $locks = [];

    public function save(Key $key): void
    {
        $resource = (string) $key;
        $token = $this->getUniqueToken($key);

        $this->evictExpired($resource);

        if (isset($this->locks[$resource]) && $this->locks[$resource]['token'] !== $token) {
            throw new LockConflictedException();
        }

        $this->locks[$resource] = [
            'token' => $token,
            'expires' => microtime(true) + 300.0,
        ];

        $key->reduceLifetime(300.0);
    }

    public function delete(Key $key): void
    {
        $resource = (string) $key;
        $token = $this->getUniqueToken($key);

        if (isset($this->locks[$resource]) && $this->locks[$resource]['token'] === $token) {
            unset($this->locks[$resource]);
        }
    }

    public function exists(Key $key): bool
    {
        $resource = (string) $key;
        $token = $this->getUniqueToken($key);

        $this->evictExpired($resource);

        return isset($this->locks[$resource]) && $this->locks[$resource]['token'] === $token;
    }

    public function putOffExpiration(Key $key, float $ttl): void
    {
        $resource = (string) $key;
        $token = $this->getUniqueToken($key);

        if (!isset($this->locks[$resource]) || $this->locks[$resource]['token'] !== $token) {
            throw new LockConflictedException();
        }

        $this->locks[$resource]['expires'] = microtime(true) + $ttl;
        $key->reduceLifetime($ttl);
    }

    private function getUniqueToken(Key $key): string
    {
        if (!$key->hasState(__CLASS__)) {
            $key->setState(__CLASS__, bin2hex(random_bytes(16)));
        }

        return $key->getState(__CLASS__);
    }

    private function evictExpired(string $resource): void
    {
        if (isset($this->locks[$resource]) && $this->locks[$resource]['expires'] <= microtime(true)) {
            unset($this->locks[$resource]);
        }
    }
}
