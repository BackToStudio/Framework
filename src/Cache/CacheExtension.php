<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache;

use BackTo\Framework\Cache\Contracts\TransientCleanerInterface;
use BackTo\Framework\Cache\Infrastructure\WordPressTransientCleaner;
use BackTo\Framework\Contracts\ExtensionInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

class CacheExtension implements ExtensionInterface
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
        $containerBuilder->register(TransientCleanerInterface::class, WordPressTransientCleaner::class);
        $containerBuilder->setAlias(WordPressTransientCleaner::class, TransientCleanerInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return CacheConfiguration::getDefaults();
    }
}
