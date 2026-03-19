<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\PostType\Contracts\PostTypeInterface;
use BackTo\Framework\PostType\Contracts\PostTypeRegistrarInterface;
use BackTo\Framework\PostType\DependencyInjection\Compiler\RegisterPostTypePass;
use BackTo\Framework\PostType\Infrastructure\WordPressPostTypeRegistrar;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class PostTypeExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\PostType\\',
            'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(PostTypeInterface::class)
            ->addTag('wordpress.post_type');

        $containerBuilder->addCompilerPass(new RegisterPostTypePass());

        $containerBuilder->register(PostTypeRegistrarInterface::class, WordPressPostTypeRegistrar::class);
        $containerBuilder->setAlias(WordPressPostTypeRegistrar::class, PostTypeRegistrarInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
