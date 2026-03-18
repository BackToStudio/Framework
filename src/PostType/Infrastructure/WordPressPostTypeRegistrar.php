<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Infrastructure;

use BackTo\Framework\PostType\Contracts\PostTypeRegistrarInterface;

use function flush_rewrite_rules;
use function post_type_exists;
use function register_post_type;

/**
 * WordPress adapter for post type registration.
 */
final class WordPressPostTypeRegistrar implements PostTypeRegistrarInterface
{
    /**
     * @param string $key
     * @param array<string, mixed> $args
     */
    public function register(string $key, array $args): void
    {
        /** @var lowercase-string&non-empty-string $key */
        register_post_type($key, $args);
    }

    public function exists(string $key): bool
    {
        return post_type_exists($key);
    }

    public function flushRewriteRules(): void
    {
        flush_rewrite_rules();
    }
}
