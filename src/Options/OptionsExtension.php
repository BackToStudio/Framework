<?php

declare(strict_types=1);

namespace BackTo\Framework\Options;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;
use BackTo\Framework\Options\Infrastructure\WordPressOptionsRepository;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class OptionsExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Options\\',
            'exclude' => '{Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(OptionsRepositoryInterface::class, WordPressOptionsRepository::class);
        $containerBuilder->setAlias(WordPressOptionsRepository::class, OptionsRepositoryInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
