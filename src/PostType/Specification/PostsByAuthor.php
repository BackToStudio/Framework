<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Specification;

use BackTo\Framework\PostType\Repository\PostQueryBuilder;

final class PostsByAuthor implements PostSpecification
{
    public function __construct(
        private readonly int $authorId,
    ) {
    }

    public function apply(PostQueryBuilder $builder): PostQueryBuilder
    {
        return $builder->author($this->authorId);
    }
}
