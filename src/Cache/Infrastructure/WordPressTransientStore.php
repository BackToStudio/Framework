<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Infrastructure;

use BackTo\Framework\Cache\Contracts\TransientStoreInterface;

use function delete_transient;
use function get_transient;
use function set_transient;

/**
 * WordPress adapter for transient storage.
 */
final class WordPressTransientStore implements TransientStoreInterface
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
