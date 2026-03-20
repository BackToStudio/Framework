<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance;

use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\SiteContextInterface;

/**
 * Resolves the current request URL for page cache keying.
 *
 * Builds a canonical URL from the current request, stripping query strings
 * and validating the host against the configured site URL.
 */
class RequestUrlResolver
{
    private readonly RequestContextInterface $requestContext;
    private readonly SiteContextInterface $siteContext;

    public function __construct(
        RequestContextInterface $requestContext,
        SiteContextInterface $siteContext,
    ) {
        $this->requestContext = $requestContext;
        $this->siteContext = $siteContext;
    }

    public function getCurrentUrl(): string
    {
        $scheme = $this->requestContext->isSecure() ? 'https' : 'http';
        $host = $this->getValidatedHost();
        $uri = $this->requestContext->getRequestUri();

        return $scheme . '://' . $host . strtok($uri, '?');
    }

    private function getValidatedHost(): string
    {
        $host = $this->requestContext->getHost();

        $siteHost = (string) parse_url($this->siteContext->getSiteUrl(), PHP_URL_HOST);

        if ($siteHost !== '' && $host !== $siteHost) {
            return $siteHost;
        }

        return $host;
    }
}
