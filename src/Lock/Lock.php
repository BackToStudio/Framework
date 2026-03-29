<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock;

use BackTo\Framework\Lock\Contracts\LockInterface;
use BackToVendor\Symfony\Component\Lock\LockInterface as SymfonyLockInterface;

/**
 * Lock adapter backed by Symfony's Lock component.
 *
 * Wraps a Symfony Lock instance and exposes it through the framework's
 * LockInterface port. Validates resource name and TTL at creation time (fail-fast).
 */
final class Lock implements LockInterface
{
    private readonly SymfonyLockInterface $lock;
    private readonly string $resource;
    private bool $acquired = false;

    public function __construct(SymfonyLockInterface $lock, string $resource)
    {
        if ($resource === '') {
            throw new \InvalidArgumentException('Lock resource name must not be empty.');
        }

        $this->lock = $lock;
        $this->resource = $resource;
    }

    public function acquire(): bool
    {
        if ($this->acquired) {
            return true;
        }

        $this->acquired = $this->lock->acquire();

        return $this->acquired;
    }

    public function release(): void
    {
        if (!$this->acquired) {
            return;
        }

        $this->lock->release();
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
