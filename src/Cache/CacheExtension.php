<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache;

use BackTo\Framework\Cache\Contracts\CacheCleanerInterface;
use BackTo\Framework\Cache\Contracts\CacheStoreInterface;
use BackTo\Framework\Cache\Infrastructure\WordPressTransientCleaner;
use BackTo\Framework\Cache\Infrastructure\WordPressTransientStore;
use BackTo\Framework\Compose\AbstractExtension;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class CacheExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Cache\\',
            'exclude' => '{Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(CacheCleanerInterface::class, WordPressTransientCleaner::class);
        $containerBuilder->setAlias(WordPressTransientCleaner::class, CacheCleanerInterface::class);

        $containerBuilder->register(CacheStoreInterface::class, WordPressTransientStore::class);
        $containerBuilder->setAlias(WordPressTransientStore::class, CacheStoreInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return CacheConfiguration::getDefaults();
    }
}
