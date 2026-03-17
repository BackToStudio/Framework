<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets;

use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Default parameter values for the Assets module.
 *
 * Override any parameter in your project's config/assets.php:
 *
 *     use BackTo\Framework\Assets\AssetsConfigurator;
 *
 *     return static function (AssetsConfigurator $assets): void {
 *         $assets->versionStrategy('timestamp');
 *     };
 */
final class AssetsConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'assets.version_strategy' => 'file',
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
