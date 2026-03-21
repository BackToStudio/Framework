<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo;

use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Default parameter values for the SEO module.
 *
 * Override any parameter in your project's config/seo.php:
 *
 *     use BackTo\Framework\Bundle\Seo\SeoConfigurator;
 *
 *     return static function (SeoConfigurator $seo): void {
 *         $seo
 *             ->titleSeparator('-')
 *             ->robotsDefault('noindex, nofollow');
 *     };
 */
final class SeoConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'seo.title_separator' => '|',
            'seo.robots_default' => 'index, follow',
            'seo.sitemap_enabled' => true,
            'seo.sitemap_users_enabled' => false,
            'seo.sitemap_excluded_post_types' => [],
            'seo.sitemap_excluded_taxonomies' => [],
            'seo.sitemap_excluded_post_ids' => [],
            'seo.sitemap_excluded_term_ids' => [],
            'seo.sitemap_max_urls' => 2000,
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
