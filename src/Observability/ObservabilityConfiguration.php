<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Default parameter values for the Observability module.
 *
 * Override any parameter in your project's config/observability.php:
 *
 *     use BackTo\Framework\Observability\ObservabilityConfigurator;
 *
 *     return static function (ObservabilityConfigurator $observability): void {
 *         $observability
 *             ->logLevel('debug')
 *             ->performanceTracking(true);
 *     };
 */
class ObservabilityConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'observability.log_level' => 'error',
            'observability.performance_tracking' => false,
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
