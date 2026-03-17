<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Contracts;

interface PostTypeRegistryInterface
{

    
    public function add(PostTypeInterface $postType): PostTypeRegistryInterface;

    
    public function getPostTypes(): array;
}
