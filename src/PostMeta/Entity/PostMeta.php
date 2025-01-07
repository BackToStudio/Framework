<?php

namespace BackTo\Framework\PostMeta\Entity;

use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;
use BackTo\Framework\PostType\Contracts\PostInterface;

class PostMeta implements PostMetaInterface
{
    private int $id;
    private PostInterface $post;
    private int $postId;
    private string $metaKey;
    private mixed $metaValue;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): PostMetaInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getPost(): PostInterface
    {
        return $this->post;
    }

    public function setPost(PostInterface $post): PostMetaInterface
    {
        $this->post = $post;
        $this->postId = $post->getId();
        return $this;
    }

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function setPostId(int $postId): PostMetaInterface
    {
        $this->postId = $postId;
        return $this;
    }

    public function getMetaKey(): string
    {
        return $this->metaKey;
    }

    public function setMetaKey(string $metaKey): PostMetaInterface
    {
        $this->metaKey = $metaKey;
        return $this;
    }

    public function getMetaValue(): mixed
    {
        return $this->metaValue;
    }

    public function setMetaValue(mixed $value): PostMetaInterface
    {
        $this->metaValue = $value;
        return $this;
    }
}