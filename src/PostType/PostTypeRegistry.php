<?php

namespace BackTo\Framework\PostType;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\PostType\Contracts\PostTypeInterface;
use BackTo\Framework\PostType\Contracts\PostTypeRegistryInterface;

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
