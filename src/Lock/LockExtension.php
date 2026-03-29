<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Lock\Contracts\LockFactoryInterface;
use BackTo\Framework\Lock\Infrastructure\CacheStoreLockStore;
use BackToVendor\Symfony\Component\Lock\LockFactory as SymfonyLockFactory;
use BackToVendor\Symfony\Component\Lock\PersistingStoreInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

final class LockExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Lock\\',
            'exclude' => '{Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        // Store: CacheStoreLockStore implements Symfony's PersistingStoreInterface.
        $containerBuilder->register(PersistingStoreInterface::class, CacheStoreLockStore::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(CacheStoreLockStore::class, PersistingStoreInterface::class);

        // Symfony LockFactory: creates Symfony Lock instances.
        $containerBuilder->register(SymfonyLockFactory::class, SymfonyLockFactory::class)
            ->setArguments([new Reference(PersistingStoreInterface::class)]);

        // Framework LockFactory adapter: wraps Symfony LockFactory.
        $containerBuilder->register(LockFactoryInterface::class, LockFactory::class)
            ->setArguments([new Reference(SymfonyLockFactory::class)]);
        $containerBuilder->setAlias(LockFactory::class, LockFactoryInterface::class);
    }
}
