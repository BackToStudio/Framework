<?php

declare(strict_types=1);

namespace BackTo\Framework\Blocks;

use BackTo\Framework\Contracts\BlockInterface;
use BackTo\Framework\Contracts\BlockStyleInterface;
use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\Blocks\Contracts\BlockStyleRegistrarInterface;
use BackTo\Framework\Blocks\DependencyInjection\Compiler\RegisterBlockPass;
use BackTo\Framework\Blocks\DependencyInjection\Compiler\RegisterBlockStylePass;
use BackTo\Framework\Blocks\Infrastructure\WordPressBlockStyleRegistrar;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

class BlocksExtension implements ExtensionInterface
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Blocks\\',
            'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(BlockInterface::class)
            ->addTag('wordpress.block');

        $containerBuilder->registerForAutoconfiguration(BlockStyleInterface::class)
            ->addTag('wordpress.block_style');

        $containerBuilder->addCompilerPass(new RegisterBlockPass());
        $containerBuilder->addCompilerPass(new RegisterBlockStylePass());

        $containerBuilder->register(BlockStyleRegistrarInterface::class, WordPressBlockStyleRegistrar::class);
        $containerBuilder->setAlias(WordPressBlockStyleRegistrar::class, BlockStyleRegistrarInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
