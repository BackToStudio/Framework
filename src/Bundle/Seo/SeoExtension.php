<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo;

use BackTo\Framework\Compose\AbstractExtension;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class SeoExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Bundle\\Seo\\',
            'exclude' => '{Tests,Contracts,Schema/Type,Schema/SchemaType.php,Schema/SchemaRef.php}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        // SEO has no compiler passes, autoconfiguration, or port bindings.
    }

    public function getDefaultConfiguration(): array
    {
        return SeoConfiguration::getDefaults();
    }
}
