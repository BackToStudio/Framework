<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Specification;

use BackTo\Framework\PostType\Entity\PostStatus;
use BackTo\Framework\PostType\Repository\PostQueryBuilder;
use BackTo\Framework\Query\SortDirection;

final class RecentPosts implements PostSpecification
{
    public function __construct(
        private readonly int $limit = 10,
    ) {
    }

    public function apply(PostQueryBuilder $builder): PostQueryBuilder
    {
        return $builder
            ->status(PostStatus::Publish)
            ->orderBy('date', SortDirection::DESC)
            ->limit($this->limit);
    }
}
