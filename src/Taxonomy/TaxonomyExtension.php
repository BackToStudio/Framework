<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyRegistrarInterface;
use BackTo\Framework\Taxonomy\Contracts\TermQueryGatewayInterface;
use BackTo\Framework\Taxonomy\DependencyInjection\Compiler\RegisterTaxonomyPass;
use BackTo\Framework\Taxonomy\Infrastructure\WordPressTaxonomyRegistrar;
use BackTo\Framework\Taxonomy\Infrastructure\WordPressTermQueryGateway;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class TaxonomyExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Taxonomy\\',
            'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(TaxonomyInterface::class)
            ->addTag('wordpress.taxonomy');

        $containerBuilder->addCompilerPass(new RegisterTaxonomyPass());

        $containerBuilder->register(TaxonomyRegistrarInterface::class, WordPressTaxonomyRegistrar::class);
        $containerBuilder->setAlias(WordPressTaxonomyRegistrar::class, TaxonomyRegistrarInterface::class);

        $containerBuilder->register(TermQueryGatewayInterface::class, WordPressTermQueryGateway::class);
        $containerBuilder->setAlias(WordPressTermQueryGateway::class, TermQueryGatewayInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
