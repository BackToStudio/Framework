<?php

declare(strict_types=1);

namespace BackTo\Framework\Options\Contracts;

/**
 * Port interface for WordPress options (wp_options).
 */
interface OptionsRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function update(string $key, mixed $value): bool;

    public function delete(string $key): bool;

    public function exists(string $key): bool;
}
