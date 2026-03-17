<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\Performance\Contracts\DatabaseOptimizerInterface;
use BackTo\Framework\Performance\Contracts\HtmlOptimizerInterface;
use BackTo\Framework\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Performance\Infrastructure\WordPressDatabaseOptimizer;
use BackTo\Framework\Performance\Infrastructure\WordPressHtmlOptimizer;
use BackTo\Framework\Performance\Infrastructure\WordPressPageCache;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

class PerformanceExtension implements ExtensionInterface
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Performance\\',
            'exclude' => '{Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $this->registerPortBindings($containerBuilder);
    }

    public function getDefaultConfiguration(): array
    {
        return [
            // Head cleanup
            'framework.performance.clean_head' => true,
            'framework.performance.disable_emojis' => true,
            'framework.performance.disable_embeds' => true,
            'framework.performance.disable_xmlrpc' => true,

            // Heartbeat
            'framework.performance.heartbeat.disable_frontend' => true,
            'framework.performance.heartbeat.admin_interval' => 60,

            // Assets
            'framework.performance.defer_scripts' => true,
            'framework.performance.defer_exclude' => ['jquery-core', 'jquery-migrate'],
            'framework.performance.remove_query_strings' => true,

            // Images
            'framework.performance.lazy_load_skip_first' => 1,
            'framework.performance.add_decoding_async' => true,
            'framework.performance.add_fetchpriority' => true,

            // HTML minification
            'framework.performance.minify_html' => false,

            // Resource hints
            'framework.performance.resource_hints.preconnect' => [],
            'framework.performance.resource_hints.dns_prefetch' => [],
            'framework.performance.resource_hints.preload' => [],

            // Revisions
            'framework.performance.revisions_limit' => 5,

            // WooCommerce
            'framework.performance.woocommerce_optimize' => true,

            // Page cache
            'framework.performance.page_cache.enabled' => false,
            'framework.performance.page_cache.ttl' => 3600,

            // Database cleanup
            'framework.performance.db_cleanup.revisions_limit' => 5,

            // Cache preloading
            'framework.performance.cache_preload.enabled' => true,
            'framework.performance.cache_preload.delay' => 5,
            'framework.performance.cache_preload.batch_size' => 50,

            // .htaccess optimization
            'framework.performance.htaccess.gzip' => true,
            'framework.performance.htaccess.browser_cache' => true,
            'framework.performance.htaccess.remove_etags' => true,
            'framework.performance.htaccess.keep_alive' => true,
            'framework.performance.htaccess.static_ttl' => 31536000,
        ];
    }

    private function registerPortBindings(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(HtmlOptimizerInterface::class, WordPressHtmlOptimizer::class);
        $containerBuilder->setAlias(WordPressHtmlOptimizer::class, HtmlOptimizerInterface::class);

        $containerBuilder->register(DatabaseOptimizerInterface::class, WordPressDatabaseOptimizer::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(WordPressDatabaseOptimizer::class, DatabaseOptimizerInterface::class);

        $containerBuilder->register(PageCacheInterface::class, WordPressPageCache::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(WordPressPageCache::class, PageCacheInterface::class);
    }
}
