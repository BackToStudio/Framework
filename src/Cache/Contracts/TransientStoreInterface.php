<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Contracts;

/**
 * Port interface for WordPress transient operations.
 *
 * Abstracts get_transient / set_transient / delete_transient
 * so that application-layer code does not call WordPress functions directly.
 */
interface TransientStoreInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $expiration = 0): bool;

    public function delete(string $key): bool;
}
