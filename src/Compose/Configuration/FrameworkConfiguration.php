<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose\Configuration;

use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Provides default framework parameter values.
 *
 * Override any parameter in your project's config/services.php:
 *
 *     $container->parameters()->set('framework.rest_api.default_namespace', 'custom/v2');
 */
class FrameworkConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            // REST API
            'framework.rest_api.default_namespace' => 'app/v1',
            'framework.rest_api.default_per_page' => 10,

            // Assets
            'framework.assets.version_strategy' => 'file',

            // Observability
            'framework.observability.log_level' => 'error',
            'framework.observability.performance_tracking' => false,
        ];
    }

    public static function apply(ContainerBuilder $containerBuilder): void
    {
        foreach (self::getDefaults() as $key => $value) {
            if (!$containerBuilder->hasParameter($key)) {
                $containerBuilder->setParameter($key, $value);
            }
        }
    }
}
