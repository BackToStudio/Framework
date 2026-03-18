<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Entity;

use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;
use BackTo\Framework\PostMeta\ValueObject\MetaKey;
use BackTo\Framework\PostMeta\Contracts\PostReferenceInterface;

final class PostMeta implements PostMetaInterface
{
    private int $id;
    private ?PostReferenceInterface $post = null;
    private int $postId = 0;
    private MetaKey $metaKey;
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

    public function getPost(): PostReferenceInterface
    {
        if ($this->post === null) {
            throw new \LogicException('Post has not been set on this PostMeta.');
        }
        return $this->post;
    }

    public function setPost(PostReferenceInterface $post): PostMetaInterface
    {
        $this->post = $post;
        $this->postId = $post->getId() ?? 0;
        return $this;
    }

    public function hasPost(): bool
    {
        return $this->post !== null;
    }

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function setPostId(int $postId): PostMetaInterface
    {
        if ($postId < 0) {
            throw new \InvalidArgumentException('Post ID cannot be negative.');
        }

        $this->postId = $postId;
        $this->post = null;
        return $this;
    }

    public function getMetaKey(): MetaKey
    {
        return $this->metaKey;
    }

    public function setMetaKey(MetaKey|string $metaKey): PostMetaInterface
    {
        $this->metaKey = $metaKey instanceof MetaKey ? $metaKey : new MetaKey($metaKey);
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