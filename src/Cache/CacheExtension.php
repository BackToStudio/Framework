<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

class CacheExtension implements ExtensionInterface
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Cache\\',
            'exclude' => '{Tests,Contracts}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        // Cache has no compiler passes, autoconfiguration, or port bindings.
    }

    public function getDefaultConfiguration(): array
    {
        return [
            'framework.cache.ttl' => 3600,
            'framework.cache.enabled' => true,
        ];
    }
}
