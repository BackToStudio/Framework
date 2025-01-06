<?php

namespace BackTo\Framework\PostMeta\Contracts;

interface PostMetaInterface
{
    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @param string $key
     * @return PostMetaInterface
     */
    public function setKey(string $key): PostMetaInterface;

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * @param string $label
     * @return PostMetaInterface
     */
    public function setLabel(string $label): PostMetaInterface;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param string $type
     * @return PostMetaInterface
     */
    public function setType(string $type): PostMetaInterface;

    /**
     * @return array
     */
    public function getArgs(): array;

    /**
     * @param array $args
     * @return PostMetaInterface
     */
    public function setArgs(array $args): PostMetaInterface;

    /**
     * @return array
     */
    public function getPostTypes(): array;

    /**
     * @param array $postTypes
     * @return PostMetaInterface
     */
    public function setPostTypes(array $postTypes): PostMetaInterface;

    /**
     * @param string $postType
     * @return PostMetaInterface
     */
    public function addPostType(string $postType): PostMetaInterface;
} 