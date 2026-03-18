<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Contracts;

/**
 * Port for reading, writing, and deleting post metadata.
 */
interface PostMetaRepositoryInterface
{
    public function create(PostMetaInterface $postMeta): PostMetaInterface;

    public function get(int $postId, string $metaKey, bool $single = true): PostMetaInterface;

    /**
     * @return int|bool Meta ID on first insert, true on update, false on failure.
     */
    public function update(PostMetaInterface $postMeta): int|bool;

    public function delete(PostMetaInterface $postMeta): bool;
}
