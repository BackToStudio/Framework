<?php

namespace Fantassin\Core\WordPress\PostType;

use Fantassin\Core\WordPress\Contracts\RegistryInterface;

class PostTypeRegistry implements RegistryInterface, PostTypeRegistryInterface
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
