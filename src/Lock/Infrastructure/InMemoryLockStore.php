<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Infrastructure;

use BackTo\Framework\Lock\Contracts\LockStoreInterface;

/**
 * In-memory lock store for testing and single-process use cases.
 *
 * Locks are held in a static array and do not survive across requests.
 * TTL is tracked but only checked on acquire/exists (no background expiry).
 */
final class InMemoryLockStore implements LockStoreInterface
{
    /** @var array<string, array{token: string, expires: int}> */
    private array $locks = [];

    public function acquire(string $resource, string $token, int $ttl): bool
    {
        $this->evictExpired($resource);

        if (isset($this->locks[$resource])) {
            // Re-entrant: same owner can re-acquire.
            if ($this->locks[$resource]['token'] === $token) {
                return true;
            }

            return false;
        }

        $this->locks[$resource] = [
            'token' => $token,
            'expires' => time() + $ttl,
        ];

        return true;
    }

    public function release(string $resource, string $token): void
    {
        if (!isset($this->locks[$resource])) {
            return;
        }

        if ($this->locks[$resource]['token'] === $token) {
            unset($this->locks[$resource]);
        }
    }

    public function exists(string $resource): bool
    {
        $this->evictExpired($resource);

        return isset($this->locks[$resource]);
    }

    private function evictExpired(string $resource): void
    {
        if (isset($this->locks[$resource]) && $this->locks[$resource]['expires'] <= time()) {
            unset($this->locks[$resource]);
        }
    }
}
