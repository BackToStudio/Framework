<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets;

use BackTo\Framework\Assets\Contracts\FileLocatorInterface;
use BackTo\Framework\Assets\Infrastructure\WordPressFileLocator;
use BackTo\Framework\Contracts\ExtensionInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

class AssetsExtension implements ExtensionInterface
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Assets\\',
            'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(FileLocatorInterface::class, WordPressFileLocator::class);
        $containerBuilder->setAlias(WordPressFileLocator::class, FileLocatorInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return AssetsConfiguration::getDefaults();
    }
}
