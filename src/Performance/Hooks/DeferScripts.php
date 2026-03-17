<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Add defer attribute to enqueued scripts and remove version query strings.
 *
 * Improves FCP/LCP by preventing render-blocking scripts.
 * Removing query strings improves CDN and proxy cache hit rates.
 */
final class DeferScripts implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;

    /** @var string[] Script handles to exclude from deferring */
    private readonly array $excludedHandles;

    private readonly bool $removeQueryStrings;

    
    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        array $excludedHandles = ['jquery-core', 'jquery-migrate'],
        bool $removeQueryStrings = true
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->excludedHandles = $excludedHandles;
        $this->removeQueryStrings = $removeQueryStrings;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('script_loader_tag', [$this, 'addDeferAttribute'], 10, 2);

        if ($this->removeQueryStrings) {
            $this->hookDispatcher->addFilter('script_loader_src', [$this, 'removeVersionQueryString']);
            $this->hookDispatcher->addFilter('style_loader_src', [$this, 'removeVersionQueryString']);
        }
    }

    /**
     * Add defer attribute to script tags.
     */
    public function addDeferAttribute(string $tag, string $handle): string
    {
        if (in_array($handle, $this->excludedHandles, true)) {
            return $tag;
        }

        if (\is_admin()) {
            return $tag;
        }

        if (str_contains($tag, 'defer') || str_contains($tag, 'async')) {
            return $tag;
        }

        return str_replace(' src=', ' defer src=', $tag);
    }

    /**
     * Remove version query string from asset URLs.
     */
    public function removeVersionQueryString(string $src): string
    {
        if (\is_admin()) {
            return $src;
        }

        if (str_contains($src, '?ver=') || str_contains($src, '&ver=')) {
            $src = (string) remove_query_arg('ver', $src);
        }

        return $src;
    }
}
