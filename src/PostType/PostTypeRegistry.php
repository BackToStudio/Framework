<?php

namespace Fantassin\Core\WordPress\PostType;

class PostTypeRegistry implements PostTypeRegistryInterface
{

    /**
     * @var PostTypeInterface[]
     */
    private $postTypes = [];

    /**
     * @param PostTypeInterface $postType
     *
     * @return PostTypeRegistry
     */
    public function add(PostTypeInterface $postType): PostTypeRegistryInterface
    {
        $this->postTypes[] = $postType;

        return $this;
    }

    /**
     * @return PostTypeInterface[]
     */
    public function getPostTypes(): array
    {
        return $this->postTypes;
    }
}
