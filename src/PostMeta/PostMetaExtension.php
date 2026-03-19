<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\PostMeta\Contracts\PostMetaRegistrarInterface;
use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;
use BackTo\Framework\PostMeta\DependencyInjection\Compiler\RegisterPostMetaStructurePass;
use BackTo\Framework\PostMeta\Infrastructure\WordPressPostMetaRegistrar;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class PostMetaExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\PostMeta\\',
            'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(PostMetaStructureInterface::class)
            ->addTag('wordpress.post_meta');

        $containerBuilder->addCompilerPass(new RegisterPostMetaStructurePass());

        $containerBuilder->register(PostMetaRegistrarInterface::class, WordPressPostMetaRegistrar::class);
        $containerBuilder->setAlias(WordPressPostMetaRegistrar::class, PostMetaRegistrarInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
