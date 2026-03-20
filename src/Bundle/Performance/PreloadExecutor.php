<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance;

use BackTo\Framework\Http\Contracts\HttpClientInterface;
use BackTo\Framework\Bundle\Performance\Contracts\PageCacheInterface;

/**
 * Executes cache preloading by sending non-blocking HTTP requests.
 *
 * Each request hits the site's own front end, which triggers
 * ServePageCache to generate and store the cached HTML.
 */
class PreloadExecutor
{
    private readonly PageCacheInterface $pageCache;
    private readonly HttpClientInterface $httpClient;

    public function __construct(
        PageCacheInterface $pageCache,
        HttpClientInterface $httpClient,
    ) {
        $this->pageCache = $pageCache;
        $this->httpClient = $httpClient;
    }

    /**
     * Preload URLs by sending non-blocking loopback HTTP requests.
     *
     * @param string[] $urls
     */
    public function preload(array $urls): void
    {
        foreach ($urls as $url) {
            if ($this->pageCache->get($url) !== null) {
                continue;
            }

            $this->httpClient->get($url, [
                'timeout'   => 30,
                'blocking'  => false,
                'sslverify' => true,
                'headers'   => [
                    'X-Cache-Preload' => '1',
                    'Cache-Control'   => 'no-cache',
                ],
                'cookies'   => [],
            ]);
        }
    }
}
