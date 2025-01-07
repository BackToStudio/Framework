<?php

namespace BackTo\Framework\PostMeta\Factory;

use Exception;
use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;
use BackTo\Framework\PostMeta\Entity\PostMeta;

class PostMetaFactory
{
    public function create(int $postId, string $metaKey, mixed $metaValue): PostMetaInterface
    {
        return (new PostMeta())
            ->setPostId($postId)
            ->setMetaKey($metaKey)
            ->setMetaValue($metaValue);
    }
} 