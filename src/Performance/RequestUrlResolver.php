<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance;

use BackTo\Framework\Contracts\RequestContextInterface;

/**
 * Resolves the current request URL for page cache keying.
 *
 * Builds a canonical URL from the current request, stripping query strings
 * and validating the host against the configured site URL.
 */
class RequestUrlResolver
{
    private readonly RequestContextInterface $requestContext;

    public function __construct(RequestContextInterface $requestContext)
    {
        $this->requestContext = $requestContext;
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

        $siteHost = (string) parse_url($this->getSiteUrl(), PHP_URL_HOST);

        if ($siteHost !== '' && $host !== $siteHost) {
            return $siteHost;
        }

        return $host;
    }

    protected function getSiteUrl(): string
    {
        return \site_url();
    }
}
