<?php

namespace BackTo\Framework\PostType\Contracts;

/**
 * Port interface for post type registration.
 *
 * Abstracts WordPress register_post_type() and related functions,
 * allowing the domain layer to remain platform-agnostic.
 */
interface PostTypeRegistrarInterface
{
    /**
     * @param string $key
     * @param array<string, mixed> $args
     */
    public function register(string $key, array $args): void;

    public function exists(string $key): bool;

    public function flushRewriteRules(): void;
}
