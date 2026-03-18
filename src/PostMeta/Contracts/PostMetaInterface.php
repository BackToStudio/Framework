<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Contracts;

use BackTo\Framework\PostMeta\ValueObject\MetaKey;
use BackTo\Framework\PostMeta\Contracts\PostReferenceInterface;

interface PostMetaInterface
{

    public function getPost(): PostReferenceInterface;


    public function setPost(PostReferenceInterface $post): PostMetaInterface;


    public function getPostId(): int;


    public function setPostId(int $postId): PostMetaInterface;


    public function getMetaKey(): MetaKey;


    public function setMetaKey(MetaKey|string $metaKey): PostMetaInterface;


    public function getMetaValue(): mixed;


    public function setMetaValue(mixed $value): PostMetaInterface;
} 
