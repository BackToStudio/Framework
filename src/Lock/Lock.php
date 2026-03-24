<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock;

use BackTo\Framework\Lock\Contracts\LockInterface;
use BackTo\Framework\Lock\Contracts\LockStoreInterface;

/**
 * Default lock implementation backed by a LockStoreInterface.
 *
 * Each Lock instance holds a unique token to ensure only the owner
 * can release it. The TTL acts as a safety net — if the holder crashes
 * without releasing, the lock auto-expires.
 */
final class Lock implements LockInterface
{
    private readonly LockStoreInterface $store;
    private readonly string $resource;
    private readonly string $token;
    private readonly int $ttl;
    private bool $acquired = false;

    public function __construct(LockStoreInterface $store, string $resource, int $ttl, ?string $token = null)
    {
        if ($resource === '') {
            throw new \InvalidArgumentException('Lock resource name must not be empty.');
        }

        if ($ttl < 1) {
            throw new \InvalidArgumentException(sprintf('Lock TTL must be at least 1 second, got %d.', $ttl));
        }

        $this->store = $store;
        $this->resource = $resource;
        $this->ttl = $ttl;
        $this->token = $token ?? bin2hex(random_bytes(16));
    }

    public function acquire(): bool
    {
        if ($this->acquired) {
            return true;
        }

        $this->acquired = $this->store->acquire($this->resource, $this->token, $this->ttl);

        return $this->acquired;
    }

    public function release(): void
    {
        if (!$this->acquired) {
            return;
        }

        $this->store->release($this->resource, $this->token);
        $this->acquired = false;
    }

    public function isAcquired(): bool
    {
        return $this->acquired;
    }

    public function getResource(): string
    {
        return $this->resource;
    }
}
