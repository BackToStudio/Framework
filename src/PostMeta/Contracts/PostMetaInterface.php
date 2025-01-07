<?php

namespace BackTo\Framework\PostMeta\Contracts;

use BackTo\Framework\PostType\Contracts\PostInterface;

interface PostMetaInterface
{
    /**
     * @return PostInterface
     */
    public function getPost(): PostInterface;

    /**
     * @param PostInterface $post
     *
     * @return PostMetaInterface
     */
    public function setPost(PostInterface $post): PostMetaInterface;

    /**
     * @return int
     */
    public function getPostId(): int;

    /**
     * @param int $postId
     *
     * @return PostMetaInterface
     */
    public function setPostId(int $postId): PostMetaInterface;

    /**
     * @return string
     */
    public function getMetaKey(): string;

    /**
     * @param string $metaKey
     *
     * @return PostMetaInterface
     */
    public function setMetaKey(string $metaKey): PostMetaInterface;

    /**
     * @return mixed
     */
    public function getMetaValue(): mixed;

    /**
     * @param mixed $value
     *
     * @return PostMetaInterface
     */
    public function setMetaValue(mixed $value): PostMetaInterface;
} 
