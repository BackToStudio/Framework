<?php

namespace Fantassin\Core\WordPress\PostType;

class PostTypeRegistry
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
    public function add(PostTypeInterface $postType): PostTypeRegistry
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
