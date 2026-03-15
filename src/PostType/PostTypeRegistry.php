<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\PostType\Contracts\PostTypeInterface;
use BackTo\Framework\PostType\Contracts\PostTypeRegistryInterface;

class PostTypeRegistry implements RegistryInterface, PostTypeRegistryInterface
{
    /** @var PostTypeInterface[] */
    private array $postTypes = [];

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
