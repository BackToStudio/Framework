<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Blocks;

use BackTo\Framework\Contracts\BlockInterface;
use BackTo\Framework\Contracts\BlockStyleInterface;
use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Bundle\Blocks\Contracts\BlockStyleRegistrarInterface;
use BackTo\Framework\Bundle\Blocks\DependencyInjection\Compiler\RegisterBlockPass;
use BackTo\Framework\Bundle\Blocks\DependencyInjection\Compiler\RegisterBlockStylePass;
use BackTo\Framework\Bundle\Blocks\Infrastructure\WordPressBlockStyleRegistrar;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class BlocksExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Bundle\\Blocks\\',
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
