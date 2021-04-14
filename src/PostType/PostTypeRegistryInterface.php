<?php


namespace Fantassin\Core\WordPress\PostType;

interface PostTypeRegistryInterface
{

    /**
     * @param PostTypeInterface $postType
     * @return PostTypeRegistryInterface
     */
    public function add(PostTypeInterface $postType): PostTypeRegistryInterface;

    /**
     * @return PostTypeInterface[]
     */
    public function getPostTypes(): array;
}
