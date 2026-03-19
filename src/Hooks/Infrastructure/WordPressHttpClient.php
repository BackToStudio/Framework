<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks\Infrastructure;

use BackTo\Framework\Contracts\HttpClientInterface;

final class WordPressHttpClient implements HttpClientInterface
{
    public function get(string $url, array $args = []): void
    {
        if (function_exists('wp_remote_get')) {
            \wp_remote_get($url, $args);
        }
    }
}
