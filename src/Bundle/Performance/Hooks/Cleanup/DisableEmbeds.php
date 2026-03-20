<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Hooks\Cleanup;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\ScriptManagerInterface;

/**
 * Disable WordPress oEmbed functionality.
 *
 * Removes the wp-embed script (~7 KB) and related discovery/REST endpoints.
 */
final class DisableEmbeds implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly ScriptManagerInterface $scriptManager;

    public function __construct(HookDispatcherInterface $hookDispatcher, ScriptManagerInterface $scriptManager)
    {
        $this->hookDispatcher = $hookDispatcher;
        $this->scriptManager = $scriptManager;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->removeAction('wp_head', 'wp_oembed_add_discovery_links');
        $this->hookDispatcher->removeAction('wp_head', 'wp_oembed_add_host_js');
        $this->hookDispatcher->addAction('wp_footer', [$this, 'deregisterEmbedScript']);
        $this->hookDispatcher->addFilter('embed_oembed_discover', '__return_false');
        $this->hookDispatcher->addFilter('rewrite_rules_array', [$this, 'removeEmbedRewriteRules']);
    }

    public function deregisterEmbedScript(): void
    {
        $this->scriptManager->deregisterScript('wp-embed');
    }

    /**
     * Remove oEmbed rewrite rules.
     *
     * @param array<string, string> $rules
     * @return array<string, string>
     */
    public function removeEmbedRewriteRules(array $rules): array
    {
        foreach ($rules as $rule => $rewrite) {
            if (str_contains($rewrite, 'embed=true')) {
                unset($rules[$rule]);
            }
        }

        return $rules;
    }
}
