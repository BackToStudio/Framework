<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;

/**
 * Fluent configurator for Performance module parameters.
 *
 * Used in config/performance.php to override defaults without
 * knowing the underlying parameter key names:
 *
 *     return static function (PerformanceConfigurator $performance): void {
 *         $performance
 *             ->pageCacheEnabled(true)
 *             ->pageCacheTtl(7200)
 *             ->minifyHtml(true)
 *             ->htaccess()->gzip(false);
 *     };
 */
class PerformanceConfigurator implements ModuleConfiguratorInterface
{
    /** @var array<string, mixed> */
    private array $overrides = [];

    // ── Head cleanup ─────────────────────────────────────────

    public function cleanHead(bool $enabled): self
    {
        $this->overrides['performance.clean_head'] = $enabled;

        return $this;
    }

    public function disableEmojis(bool $disabled): self
    {
        $this->overrides['performance.disable_emojis'] = $disabled;

        return $this;
    }

    public function disableEmbeds(bool $disabled): self
    {
        $this->overrides['performance.disable_embeds'] = $disabled;

        return $this;
    }

    public function disableXmlrpc(bool $disabled): self
    {
        $this->overrides['performance.disable_xmlrpc'] = $disabled;

        return $this;
    }

    // ── Heartbeat ────────────────────────────────────────────

    public function heartbeatDisableFrontend(bool $disabled): self
    {
        $this->overrides['performance.heartbeat.disable_frontend'] = $disabled;

        return $this;
    }

    public function heartbeatAdminInterval(int $seconds): self
    {
        $this->overrides['performance.heartbeat.admin_interval'] = $seconds;

        return $this;
    }

    // ── Assets ───────────────────────────────────────────────

    public function deferScripts(bool $enabled): self
    {
        $this->overrides['performance.defer_scripts'] = $enabled;

        return $this;
    }

    /**
     * @param string[] $handles Script handles to exclude from defer.
     */
    public function deferExclude(array $handles): self
    {
        $this->overrides['performance.defer_exclude'] = $handles;

        return $this;
    }

    public function removeQueryStrings(bool $enabled): self
    {
        $this->overrides['performance.remove_query_strings'] = $enabled;

        return $this;
    }

    // ── Images ───────────────────────────────────────────────

    public function lazyLoadSkipFirst(int $count): self
    {
        $this->overrides['performance.lazy_load_skip_first'] = $count;

        return $this;
    }

    public function addDecodingAsync(bool $enabled): self
    {
        $this->overrides['performance.add_decoding_async'] = $enabled;

        return $this;
    }

    public function addFetchpriority(bool $enabled): self
    {
        $this->overrides['performance.add_fetchpriority'] = $enabled;

        return $this;
    }

    // ── HTML minification ────────────────────────────────────

    public function minifyHtml(bool $enabled): self
    {
        $this->overrides['performance.minify_html'] = $enabled;

        return $this;
    }

    // ── Remove unused CSS ─────────────────────────────────

    public function removeUnusedCss(bool $enabled): self
    {
        $this->overrides['performance.remove_unused_css'] = $enabled;

        return $this;
    }

    // ── Resource hints ───────────────────────────────────────

    /**
     * @param string[] $origins Origins to preconnect to (e.g. ['https://fonts.googleapis.com']).
     */
    public function preconnect(array $origins): self
    {
        $this->overrides['performance.resource_hints.preconnect'] = $origins;

        return $this;
    }

    /**
     * @param string[] $domains Domains to DNS-prefetch.
     */
    public function dnsPrefetch(array $domains): self
    {
        $this->overrides['performance.resource_hints.dns_prefetch'] = $domains;

        return $this;
    }

    /**
     * @param string[] $resources Resources to preload.
     */
    public function preload(array $resources): self
    {
        $this->overrides['performance.resource_hints.preload'] = $resources;

        return $this;
    }

    // ── Revisions ────────────────────────────────────────────

    public function revisionsLimit(int $limit): self
    {
        $this->overrides['performance.revisions_limit'] = $limit;

        return $this;
    }

    // ── WooCommerce ──────────────────────────────────────────

    public function woocommerceOptimize(bool $enabled): self
    {
        $this->overrides['performance.woocommerce_optimize'] = $enabled;

        return $this;
    }

    // ── Page cache ───────────────────────────────────────────

    public function pageCacheEnabled(bool $enabled): self
    {
        $this->overrides['performance.page_cache.enabled'] = $enabled;

        return $this;
    }

    public function pageCacheTtl(int $seconds): self
    {
        $this->overrides['performance.page_cache.ttl'] = $seconds;

        return $this;
    }

    // ── Database cleanup ─────────────────────────────────────

    public function dbCleanupRevisionsLimit(int $limit): self
    {
        $this->overrides['performance.db_cleanup.revisions_limit'] = $limit;

        return $this;
    }

    // ── Cache preloading ─────────────────────────────────────

    public function cachePreloadEnabled(bool $enabled): self
    {
        $this->overrides['performance.cache_preload.enabled'] = $enabled;

        return $this;
    }

    public function cachePreloadDelay(int $seconds): self
    {
        $this->overrides['performance.cache_preload.delay'] = $seconds;

        return $this;
    }

    public function cachePreloadBatchSize(int $size): self
    {
        $this->overrides['performance.cache_preload.batch_size'] = $size;

        return $this;
    }

    // ── .htaccess optimization ───────────────────────────────

    public function htaccessGzip(bool $enabled): self
    {
        $this->overrides['performance.htaccess.gzip'] = $enabled;

        return $this;
    }

    public function htaccessBrowserCache(bool $enabled): self
    {
        $this->overrides['performance.htaccess.browser_cache'] = $enabled;

        return $this;
    }

    public function htaccessRemoveEtags(bool $enabled): self
    {
        $this->overrides['performance.htaccess.remove_etags'] = $enabled;

        return $this;
    }

    public function htaccessKeepAlive(bool $enabled): self
    {
        $this->overrides['performance.htaccess.keep_alive'] = $enabled;

        return $this;
    }

    public function htaccessStaticTtl(int $seconds): self
    {
        $this->overrides['performance.htaccess.static_ttl'] = $seconds;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toParameters(): array
    {
        return $this->overrides;
    }
}
