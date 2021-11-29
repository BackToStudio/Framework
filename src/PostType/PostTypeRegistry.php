<?php

namespace Fantassin\Core\WordPress\PostType;

use Fantassin\Core\WordPress\Contracts\RegistryInterface;
use Fantassin\Core\WordPress\PostType\Contracts\PostTypeInterface;
use Fantassin\Core\WordPress\PostType\Contracts\PostTypeRegistryInterface;

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
