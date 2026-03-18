<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance;

/**
 * Resolves the current request URL for page cache keying.
 *
 * Builds a canonical URL from the current request, stripping query strings
 * and validating the host against the configured site URL.
 */
class RequestUrlResolver
{
    public function getCurrentUrl(): string
    {
        $scheme = $this->isSSL() ? 'https' : 'http';
        $host = $this->getValidatedHost();
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $scheme . '://' . $host . strtok($uri, '?');
    }

    private function getValidatedHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $siteHost = (string) parse_url($this->getSiteUrl(), PHP_URL_HOST);

        if ($siteHost !== '' && $host !== $siteHost) {
            return $siteHost;
        }

        return $host;
    }

    protected function isSSL(): bool
    {
        return \is_ssl();
    }

    protected function getSiteUrl(): string
    {
        return \site_url();
    }
}
