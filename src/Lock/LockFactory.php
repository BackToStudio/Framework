<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock;

use BackTo\Framework\Lock\Contracts\LockFactoryInterface;
use BackTo\Framework\Lock\Contracts\LockInterface;
use BackToVendor\Symfony\Component\Lock\LockFactory as SymfonyLockFactory;

/**
 * Lock factory adapter backed by Symfony's Lock component.
 *
 * Wraps a Symfony LockFactory and produces framework LockInterface instances.
 */
final class LockFactory implements LockFactoryInterface
{
    private readonly SymfonyLockFactory $factory;

    public function __construct(SymfonyLockFactory $factory)
    {
        $this->factory = $factory;
    }

    public function create(string $resource, int $ttl = 300): LockInterface
    {
        if ($resource === '') {
            throw new \InvalidArgumentException('Lock resource name must not be empty.');
        }

        if ($ttl < 1) {
            throw new \InvalidArgumentException(sprintf('Lock TTL must be at least 1 second, got %d.', $ttl));
        }

        $symfonyLock = $this->factory->createLock($resource, (float) $ttl, false);

        return new Lock($symfonyLock, $resource);
    }
}
