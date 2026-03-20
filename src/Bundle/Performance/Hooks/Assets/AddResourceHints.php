<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Hooks\Assets;

use BackTo\Framework\Contracts\EscaperInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Add resource hints (preconnect, dns-prefetch, preload) to improve TTFB/FCP.
 *
 * Allows declarative configuration of resource hints via DI parameters.
 */
final class AddResourceHints implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly EscaperInterface $escaper;

    /** @var string[] Domains to preconnect to */
    private readonly array $preconnect;

    /** @var string[] Domains to DNS prefetch */
    private readonly array $dnsPrefetch;

    /** @var array<array{url: string, as: string, type?: string}> Resources to preload */
    private readonly array $preload;

    /**
     * @param string[] $preconnect
     * @param string[] $dnsPrefetch
     * @param array<array{url: string, as: string, type?: string}> $preload
     */
    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        EscaperInterface $escaper,
        array $preconnect = [],
        array $dnsPrefetch = [],
        array $preload = []
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->escaper = $escaper;
        $this->preconnect = $preconnect;
        $this->dnsPrefetch = $dnsPrefetch;
        $this->preload = $preload;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('wp_resource_hints', [$this, 'addHints'], 10, 2);

        if (!empty($this->preload)) {
            $this->hookDispatcher->addAction('wp_head', [$this, 'addPreloadLinks'], 1);
        }
    }

    /**
     * Add preconnect and dns-prefetch hints.
     *
     * @param string[] $urls
     * @return string[]
     */
    public function addHints(array $urls, string $relationType): array
    {
        if ($relationType === 'preconnect') {
            foreach ($this->preconnect as $domain) {
                $urls[] = $domain;
            }
        }

        if ($relationType === 'dns-prefetch') {
            foreach ($this->dnsPrefetch as $domain) {
                $urls[] = $domain;
            }
        }

        return $urls;
    }

    /**
     * Output preload link tags in the head.
     */
    public function addPreloadLinks(): void
    {
        foreach ($this->preload as $resource) {
            $url = $this->escaper->escUrl($resource['url']);
            $as = $this->escaper->escAttr($resource['as']);
            $type = isset($resource['type']) ? ' type="' . $this->escaper->escAttr($resource['type']) . '"' : '';
            $crossorigin = in_array($resource['as'], ['font', 'fetch'], true) ? ' crossorigin' : '';

            echo '<link rel="preload" href="' . $url . '" as="' . $as . '"' . $type . $crossorigin . '>' . "\n";
        }
    }
}
