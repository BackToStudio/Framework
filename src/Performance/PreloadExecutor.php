<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance;

use BackTo\Framework\Performance\Contracts\PageCacheInterface;

/**
 * Executes cache preloading by sending non-blocking HTTP requests.
 *
 * Each request hits the site's own front end, which triggers
 * ServePageCache to generate and store the cached HTML.
 */
class PreloadExecutor
{
    public function __construct(
        private readonly PageCacheInterface $pageCache,
    ) {
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

            \wp_remote_get($url, [
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
