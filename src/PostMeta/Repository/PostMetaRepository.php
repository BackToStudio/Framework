<?php

namespace BackTo\Framework\PostMeta\Repository;

use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;
use BackTo\Framework\PostMeta\Factory\PostMetaFactory;
use function get_post_meta;
use function update_post_meta;
use function delete_post_meta;

class PostMetaRepository
{
    private PostMetaFactory $factory;

    public function __construct(PostMetaFactory $factory)
    {
        $this->factory = $factory;
    }

    public function create(PostMetaInterface $postMeta): PostMetaInterface
    {
        return $this->update($postMeta);
    }

    public function get(int $postId, string $metaKey, bool $single = true): PostMetaInterface
    {
        $postMetaValue = get_post_meta($postId, $metaKey, $single);
        return $this->factory->create($postId, $metaKey, $postMetaValue);
    }

    public function update(PostMetaInterface $postMeta)
    {
        return update_post_meta($postMeta->getPostId(), $postMeta->getMetaKey(), $postMeta->getMetaValue());
    }

    public function delete(PostMetaInterface $postMeta): bool
    {
        return delete_post_meta($postMeta->getPostId(), $postMeta->getMetaKey());
    }
} 