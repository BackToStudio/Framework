<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Infrastructure;

use BackTo\Framework\PostMeta\Contracts\PostMetaRegistrarInterface;

use function register_post_meta;

/**
 * WordPress adapter for post meta registration.
 */
final class WordPressPostMetaRegistrar implements PostMetaRegistrarInterface
{
    /**
     * @param string $postType
     * @param string $metaKey
     * @param array<string, mixed> $args
     */
    public function register(string $postType, string $metaKey, array $args): void
    {
        register_post_meta($postType, $metaKey, $args);
    }
}
