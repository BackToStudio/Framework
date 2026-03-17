<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Contracts;

use BackTo\Framework\PostType\Contracts\PostInterface;

interface PostMetaInterface
{
    
    public function getPost(): PostInterface;

    
    public function setPost(PostInterface $post): PostMetaInterface;

    
    public function getPostId(): int;

    
    public function setPostId(int $postId): PostMetaInterface;

    
    public function getMetaKey(): string;

    
    public function setMetaKey(string $metaKey): PostMetaInterface;

    
    public function getMetaValue(): mixed;

    
    public function setMetaValue(mixed $value): PostMetaInterface;
} 
