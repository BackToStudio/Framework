<?php

namespace BackTo\Framework\PostMeta\Contracts;

/**
 * Port interface for post meta registration.
 *
 * Abstracts WordPress register_post_meta(),
 * allowing the domain layer to remain platform-agnostic.
 */
interface PostMetaRegistrarInterface
{
    /**
     * @param string $postType
     * @param string $metaKey
     * @param array<string, mixed> $args
     */
    public function register(string $postType, string $metaKey, array $args): void;
}
