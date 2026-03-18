<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Specification;

use BackTo\Framework\PostType\Repository\PostQueryBuilder;

final class PostsByType implements PostSpecification
{
    public function __construct(
        private readonly string $postType,
    ) {
    }

    public function apply(PostQueryBuilder $builder): PostQueryBuilder
    {
        return $builder->postType($this->postType);
    }
}
