<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Specification;

use BackTo\Framework\PostType\Repository\PostQueryBuilder;

interface PostSpecification
{
    public function apply(PostQueryBuilder $builder): PostQueryBuilder;
}
