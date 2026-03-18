<?php

declare(strict_types=1);

namespace BackTo\Framework\Options\Infrastructure;

use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;

use function delete_option;
use function get_option;
use function update_option;

/**
 * WordPress adapter for the Options port.
 */
final class WordPressOptionsRepository implements OptionsRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return get_option($key, $default);
    }

    public function update(string $key, mixed $value): bool
    {
        return update_option($key, $value);
    }

    public function delete(string $key): bool
    {
        return delete_option($key);
    }

    public function exists(string $key): bool
    {
        $value = get_option($key, $this);

        return $value !== $this;
    }
}
