<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance;

use BackTo\Framework\Contracts\RequestContextInterface;

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
    private readonly RequestContextInterface $requestContext;

    /** @var string[] */
    private readonly array $excludedPrefixes;

    /**
     * @param string[] $excludedPrefixes URL path prefixes to exclude from caching.
     */
    public function __construct(
        RequestContextInterface $requestContext,
        array $excludedPrefixes = ['/wp-admin', '/wp-json', '/wp-login.php', '/wp-cron.php', '/xmlrpc.php']
    ) {
        $this->requestContext = $requestContext;
        $this->excludedPrefixes = $excludedPrefixes;
    }

    public function isCacheable(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        if ($this->requestContext->getMethod() !== 'GET') {
            return false;
        }

        if ($this->isUserLoggedIn()) {
            return false;
        }

        if ($this->requestContext->hasQueryParams()) {
            return false;
        }

        $requestUri = $this->requestContext->getRequestUri();

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

    protected function isUserLoggedIn(): bool
    {
        return \is_user_logged_in();
    }
}
