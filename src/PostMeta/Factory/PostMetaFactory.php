<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Factory;

use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;
use BackTo\Framework\PostMeta\Entity\PostMeta;
use BackTo\Framework\PostMeta\ValueObject\MetaKey;

final class PostMetaFactory
{
    public function create(int $postId, MetaKey|string $metaKey, mixed $metaValue): PostMetaInterface
    {
        return (new PostMeta())
            ->setPostId($postId)
            ->setMetaKey($metaKey)
            ->setMetaValue($metaValue);
    }
} 