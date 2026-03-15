<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose\Configuration;

use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Provides default framework parameter values.
 *
 * Override any parameter in your project's config/services.php:
 *
 *     $container->parameters()->set('framework.cache.ttl', 7200);
 */
class FrameworkConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            // Cache
            'framework.cache.ttl' => 3600,
            'framework.cache.enabled' => true,

            // SEO
            'framework.seo.title_separator' => '|',
            'framework.seo.robots_default' => 'index, follow',

            // REST API
            'framework.rest_api.default_namespace' => 'app/v1',
            'framework.rest_api.default_per_page' => 10,

            // Assets
            'framework.assets.version_strategy' => 'file',

            // Observability
            'framework.observability.log_level' => 'error',
            'framework.observability.performance_tracking' => false,

            // Security
            'framework.security.headers_enabled' => true,
            'framework.security.xmlrpc_disabled' => true,
            'framework.security.hide_version' => true,
            'framework.security.csp_report_only' => false,
            'framework.security.password_min_length' => 12,
            'framework.security.max_concurrent_sessions' => 1,
            'framework.security.rest_api_require_auth' => true,
            'framework.security.disable_file_editor' => true,
            'framework.security.two_factor_enabled' => false,
            'framework.security.two_factor_issuer' => 'WordPress',

            // Performance — Head cleanup
            'framework.performance.clean_head' => true,
            'framework.performance.disable_emojis' => true,
            'framework.performance.disable_embeds' => true,
            'framework.performance.disable_xmlrpc' => true,

            // Performance — Heartbeat
            'framework.performance.heartbeat.disable_frontend' => true,
            'framework.performance.heartbeat.admin_interval' => 60,

            // Performance — Assets
            'framework.performance.defer_scripts' => true,
            'framework.performance.defer_exclude' => ['jquery-core', 'jquery-migrate'],
            'framework.performance.remove_query_strings' => true,

            // Performance — Images
            'framework.performance.lazy_load_skip_first' => 1,
            'framework.performance.add_decoding_async' => true,
            'framework.performance.add_fetchpriority' => true,

            // Performance — HTML minification
            'framework.performance.minify_html' => false,

            // Performance — Resource hints
            'framework.performance.resource_hints.preconnect' => [],
            'framework.performance.resource_hints.dns_prefetch' => [],
            'framework.performance.resource_hints.preload' => [],

            // Performance — Revisions
            'framework.performance.revisions_limit' => 5,

            // Performance — WooCommerce
            'framework.performance.woocommerce_optimize' => true,

            // Performance — Page cache
            'framework.performance.page_cache.enabled' => false,
            'framework.performance.page_cache.ttl' => 3600,

            // Performance — Database cleanup
            'framework.performance.db_cleanup.revisions_limit' => 5,
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
