<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Actions;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Seo\SeoConfig;
use BackTo\Framework\Seo\SeoManager;

/**
 * Disable schema.org output from Yoast SEO and SEOPress to avoid duplicates
 * when the framework generates its own structured data.
 *
 * Can be disabled via config/seo.php: 'schema.disable_plugin_schema' => false
 */
final class DisablePluginSchema implements Hooks
{
    private readonly SeoManager $seoManager;

    private readonly SeoConfig $seoConfig;

    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(SeoManager $seoManager, SeoConfig $seoConfig, HookDispatcherInterface $hookDispatcher)
    {
        $this->seoManager = $seoManager;
        $this->seoConfig = $seoConfig;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        if (!$this->seoConfig->shouldDisablePluginSchema()) {
            return;
        }

        if (!$this->seoManager->hasProvider()) {
            return;
        }

        $provider = $this->seoManager->getProvider();

        match ($provider->getName()) {
            'yoast' => $this->disableYoastSchema(),
            'seopress' => $this->disableSeoPressSchema(),
            default => null,
        };
    }

    private function disableYoastSchema(): void
    {
        $this->hookDispatcher->addFilter('wpseo_json_ld_output', '__return_empty_array');
    }

    private function disableSeoPressSchema(): void
    {
        $this->hookDispatcher->addFilter('seopress_schemas_auto_enabled', '__return_false');
    }
}
