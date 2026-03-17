<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache;

use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Default parameter values for the Cache module.
 *
 * Override any parameter in your project's config/cache.php:
 *
 *     use BackTo\Framework\Cache\CacheConfigurator;
 *
 *     return static function (CacheConfigurator $cache): void {
 *         $cache
 *             ->ttl(7200)
 *             ->enabled(false);
 *     };
 */
class CacheConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'cache.ttl' => 3600,
            'cache.enabled' => true,
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
