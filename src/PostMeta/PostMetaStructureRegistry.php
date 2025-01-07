<?php

namespace BackTo\Framework\PostMeta;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;

class PostMetaStructureRegistry implements RegistryInterface
{
    /**
     * @var PostMetaStructureInterface[]
     */
    private $postMetaStructures = [];

    public function add(PostMetaStructureInterface $postMetaStructure): PostMetaStructureRegistry
    {
        $this->postMetaStructures[] = $postMetaStructure;

        return $this;
    }

    public function getPostMetaStructures(): array
    {
        return $this->postMetaStructures;
    }
}
