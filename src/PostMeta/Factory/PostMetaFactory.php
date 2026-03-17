<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Factory;

use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;
use BackTo\Framework\PostMeta\Entity\PostMeta;

final class PostMetaFactory
{
    public function create(int $postId, string $metaKey, mixed $metaValue): PostMetaInterface
    {
        return (new PostMeta())
            ->setPostId($postId)
            ->setMetaKey($metaKey)
            ->setMetaValue($metaValue);
    }
} 