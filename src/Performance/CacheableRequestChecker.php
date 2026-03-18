<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance;

/**
 * Determines whether the current request is eligible for page caching.
 *
 * A request is cacheable when it is:
 * - Not an admin page
 * - A GET request with no query parameters
 * - From a non-logged-in visitor
 * - Not targeting an excluded URL prefix
 */
class CacheableRequestChecker
{
    /** @var string[] */
    private readonly array $excludedPrefixes;

    /**
     * @param string[] $excludedPrefixes URL path prefixes to exclude from caching.
     */
    public function __construct(
        array $excludedPrefixes = ['/wp-admin', '/wp-json', '/wp-login.php', '/wp-cron.php', '/xmlrpc.php']
    ) {
        $this->excludedPrefixes = $excludedPrefixes;
    }

    public function isCacheable(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        if ($this->getRequestMethod() !== 'GET') {
            return false;
        }

        if ($this->isUserLoggedIn()) {
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

    protected function isAdmin(): bool
    {
        return \is_admin();
    }

    protected function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? '';
    }

    protected function isUserLoggedIn(): bool
    {
        return \is_user_logged_in();
    }
}
