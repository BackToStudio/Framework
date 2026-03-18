<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Specification;

use BackTo\Framework\PostMeta\ValueObject\MetaKey;
use BackTo\Framework\PostType\Repository\MetaCompare;
use BackTo\Framework\PostType\Repository\PostQueryBuilder;

final class PostsWithMeta implements PostSpecification
{
    public function __construct(
        private readonly MetaKey|string $key,
        private readonly mixed $value = null,
        private readonly MetaCompare $compare = MetaCompare::EXISTS,
    ) {
    }

    public function apply(PostQueryBuilder $builder): PostQueryBuilder
    {
        if ($this->value === null && $this->compare === MetaCompare::EXISTS) {
            return $builder->whereMetaExists($this->key);
        }

        return $builder->whereMeta($this->key, $this->value, $this->compare);
    }
}
