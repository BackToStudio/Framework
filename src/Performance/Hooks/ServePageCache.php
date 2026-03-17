<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Performance\Contracts\PageCacheInterface;

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
class ServePageCache implements Hooks
{
    private HookDispatcherInterface $hookDispatcher;
    private PageCacheInterface $pageCache;
    private int $ttl;

    /** @var string[] URL path prefixes to exclude from caching */
    private array $excludedPrefixes;

    /**
     * @param string[] $excludedPrefixes
     */
    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        PageCacheInterface $pageCache,
        int $ttl = 3600,
        array $excludedPrefixes = ['/wp-admin', '/wp-json', '/wp-login.php', '/wp-cron.php', '/xmlrpc.php']
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->pageCache = $pageCache;
        $this->ttl = $ttl;
        $this->excludedPrefixes = $excludedPrefixes;
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
        if (!$this->isCacheable()) {
            return;
        }

        $url = $this->getCurrentUrl();
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
        if (!$this->isCacheable()) {
            return;
        }

        if (\is_404() || \is_search()) {
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
            $this->pageCache->put($this->getCurrentUrl(), $html, $this->ttl);
            $html .= "\n<!-- X-Page-Cache: MISS -->";
        }

        return $html;
    }

    private function isCacheable(): bool
    {
        if (\is_admin()) {
            return false;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return false;
        }

        if (\is_user_logged_in()) {
            return false;
        }

        if (!empty($_GET)) {
            return false;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        foreach ($this->excludedPrefixes as $prefix) {
            if (str_starts_with($requestUri, $prefix)) {
                return false;
            }
        }

        return true;
    }

    private function getCurrentUrl(): string
    {
        $scheme = \is_ssl() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $scheme . '://' . $host . strtok($uri, '?');
    }
}
