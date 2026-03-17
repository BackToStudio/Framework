<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo;

use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Default parameter values for the SEO module.
 *
 * Override any parameter in your project's config/seo.php:
 *
 *     use BackTo\Framework\Seo\SeoConfigurator;
 *
 *     return static function (SeoConfigurator $seo): void {
 *         $seo
 *             ->titleSeparator('-')
 *             ->robotsDefault('noindex, nofollow');
 *     };
 */
class SeoConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'seo.title_separator' => '|',
            'seo.robots_default' => 'index, follow',
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
