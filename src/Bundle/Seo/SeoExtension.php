<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo;

use BackTo\Framework\Bundle\Seo\DependencyInjection\Compiler\ConfigureSitemapsPass;
use BackTo\Framework\Compose\AbstractExtension;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class SeoExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Bundle\\Seo\\',
            'exclude' => '{Tests,Contracts,DependencyInjection,Schema/Type,Schema/SchemaType.php,Schema/SchemaRef.php}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addCompilerPass(new ConfigureSitemapsPass());
    }

    public function getDefaultConfiguration(): array
    {
        return SeoConfiguration::getDefaults();
    }
}
