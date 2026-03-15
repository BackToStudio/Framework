<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;

class PostMetaStructureRegistry implements RegistryInterface
{
    /** @var PostMetaStructureInterface[] */
    private array $postMetaStructures = [];

    public function add(PostMetaStructureInterface $postMetaStructure): self
    {
        $this->postMetaStructures[] = $postMetaStructure;

        return $this;
    }

    /**
     * @return PostMetaStructureInterface[]
     */
    public function getPostMetaStructures(): array
    {
        return $this->postMetaStructures;
    }
}
