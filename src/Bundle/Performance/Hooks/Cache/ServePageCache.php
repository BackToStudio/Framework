<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Hooks\Cache;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\QueryContextInterface;
use BackTo\Framework\Bundle\Performance\CacheableRequestChecker;
use BackTo\Framework\Bundle\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Bundle\Performance\RequestUrlResolver;

/**
 * Serve cached HTML pages and capture output for caching.
 *
 * For non-logged-in visitors on GET requests:
 * 1. On `init` (early): check if a cached version exists and serve it immediately.
 * 2. On `template_redirect`: start output buffering to capture the rendered page.
 * 3. On `shutdown`: store the captured HTML in the page cache.
 *
 * Excluded pages: admin, REST API, POST requests, logged-in users,
 * WooCommerce cart/checkout, search results, 404s.
 */
final class ServePageCache implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly PageCacheInterface $pageCache;
    private readonly CacheableRequestChecker $requestChecker;
    private readonly RequestUrlResolver $urlResolver;
    private readonly QueryContextInterface $queryContext;
    private readonly int $ttl;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        PageCacheInterface $pageCache,
        CacheableRequestChecker $requestChecker,
        RequestUrlResolver $urlResolver,
        QueryContextInterface $queryContext,
        int $ttl = 3600,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->pageCache = $pageCache;
        $this->requestChecker = $requestChecker;
        $this->urlResolver = $urlResolver;
        $this->queryContext = $queryContext;
        $this->ttl = $ttl;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'serveCachedPage'], 0);
        $this->hookDispatcher->addAction('template_redirect', [$this, 'startOutputBuffering']);
    }

    /**
     * Serve a cached page if available (very early in the request).
     */
    public function serveCachedPage(): void
    {
        if (!$this->requestChecker->isCacheable()) {
            return;
        }

        $url = $this->urlResolver->getCurrentUrl();
        $html = $this->pageCache->get($url);

        if ($html === null) {
            return;
        }

        // @codeCoverageIgnoreStart
        header('X-Page-Cache: HIT');
        echo $html;
        exit;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Start output buffering to capture the rendered page.
     */
    public function startOutputBuffering(): void
    {
        if (!$this->requestChecker->isCacheable()) {
            return;
        }

        if ($this->queryContext->is404() || $this->queryContext->isSearch()) {
            return;
        }

        \ob_start([$this, 'captureOutput']);
    }

    /**
     * Capture rendered output and store it in the page cache.
     */
    public function captureOutput(string $html): string
    {
        if (strlen($html) > 0 && !str_contains($html, 'Fatal error')) {
            $this->pageCache->put($this->urlResolver->getCurrentUrl(), $html, $this->ttl);
            $html .= "\n<!-- X-Page-Cache: MISS -->";
        }

        return $html;
    }
}
