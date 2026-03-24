<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Lock\Contracts\LockFactoryInterface;
use BackTo\Framework\Lock\Contracts\LockStoreInterface;
use BackTo\Framework\Lock\Infrastructure\CacheStoreLockStore;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

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
        // Store: CacheStoreLockStore (backed by whatever cache strategy is active).
        $containerBuilder->register(LockStoreInterface::class, CacheStoreLockStore::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(CacheStoreLockStore::class, LockStoreInterface::class);

        // Factory: create locks via the port interface.
        $containerBuilder->register(LockFactoryInterface::class, LockFactory::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(LockFactory::class, LockFactoryInterface::class);
    }
}
