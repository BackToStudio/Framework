<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Specification;

use BackTo\Framework\PostType\Entity\PostStatus;
use BackTo\Framework\PostType\Repository\PostQueryBuilder;

final class PublishedPosts implements PostSpecification
{
    public function apply(PostQueryBuilder $builder): PostQueryBuilder
    {
        return $builder->status(PostStatus::Publish);
    }
}
