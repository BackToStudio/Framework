<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance;

use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Default parameter values for the Performance module.
 *
 * Override any parameter in your project's config/performance.php
 * using the fluent PerformanceConfigurator:
 *
 *     return static function (PerformanceConfigurator $performance): void {
 *         $performance
 *             ->pageCacheEnabled(true)
 *             ->pageCacheTtl(7200)
 *             ->htaccessGzip(false);
 *     };
 */
final class PerformanceConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            // Head cleanup
            'performance.clean_head' => true,
            'performance.disable_emojis' => true,
            'performance.disable_embeds' => true,
            'performance.disable_xmlrpc' => true,

            // Heartbeat
            'performance.heartbeat.disable_frontend' => true,
            'performance.heartbeat.admin_interval' => 60,

            // Assets
            'performance.defer_scripts' => true,
            'performance.defer_exclude' => ['jquery-core', 'jquery-migrate'],
            'performance.remove_query_strings' => true,

            // Images
            'performance.lazy_load_skip_first' => 1,
            'performance.add_decoding_async' => true,
            'performance.add_fetchpriority' => true,

            // HTML minification
            'performance.minify_html' => false,

            // Remove unused CSS
            'performance.remove_unused_css' => false,

            // Resource hints
            'performance.resource_hints.preconnect' => [],
            'performance.resource_hints.dns_prefetch' => [],
            'performance.resource_hints.preload' => [],

            // Revisions
            'performance.revisions_limit' => 5,

            // WooCommerce
            'performance.woocommerce_optimize' => true,

            // Page cache
            'performance.page_cache.enabled' => false,
            'performance.page_cache.ttl' => 3600,

            // Database cleanup
            'performance.db_cleanup.revisions_limit' => 5,

            // Cache preloading
            'performance.cache_preload.enabled' => true,
            'performance.cache_preload.delay' => 5,
            'performance.cache_preload.batch_size' => 50,

            // .htaccess optimization
            'performance.htaccess.gzip' => true,
            'performance.htaccess.browser_cache' => true,
            'performance.htaccess.remove_etags' => true,
            'performance.htaccess.keep_alive' => true,
            'performance.htaccess.static_ttl' => 31536000,
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
