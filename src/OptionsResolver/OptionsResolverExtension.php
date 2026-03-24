<?php

declare(strict_types=1);

namespace BackTo\Framework\OptionsResolver;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\OptionsResolver\Contracts\OptionsResolverInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class OptionsResolverExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\OptionsResolver\\',
            'exclude' => '{Tests,Contracts}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(OptionsResolverInterface::class, OptionsResolver::class)
            ->setShared(false);
        $containerBuilder->setAlias(OptionsResolver::class, OptionsResolverInterface::class);
    }
}
