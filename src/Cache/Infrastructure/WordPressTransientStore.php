<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Infrastructure;

use BackTo\Framework\Cache\Contracts\CacheStoreInterface;

use function delete_transient;
use function get_transient;
use function set_transient;

/**
 * WordPress adapter for cache storage backed by WordPress transients.
 */
final class WordPressTransientStore implements CacheStoreInterface
{
    public function get(string $key): mixed
    {
        return get_transient($key);
    }

    public function set(string $key, mixed $value, int $expiration = 0): bool
    {
        return set_transient($key, $value, $expiration);
    }

    public function delete(string $key): bool
    {
        return delete_transient($key);
    }
}
