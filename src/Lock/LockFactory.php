<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock;

use BackTo\Framework\Lock\Contracts\LockFactoryInterface;
use BackTo\Framework\Lock\Contracts\LockInterface;
use BackTo\Framework\Lock\Contracts\LockStoreInterface;

/**
 * Creates Lock instances backed by a configurable store.
 */
final class LockFactory implements LockFactoryInterface
{
    private readonly LockStoreInterface $store;

    public function __construct(LockStoreInterface $store)
    {
        $this->store = $store;
    }

    public function create(string $resource, int $ttl = 300): LockInterface
    {
        return new Lock($this->store, $resource, $ttl);
    }
}
