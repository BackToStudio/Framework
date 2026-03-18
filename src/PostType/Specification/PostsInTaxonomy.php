<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Specification;

use BackTo\Framework\PostType\Repository\PostQueryBuilder;

final class PostsInTaxonomy implements PostSpecification
{
    /**
     * @param string $taxonomy The taxonomy slug.
     * @param int[] $termIds The term IDs to match.
     */
    public function __construct(
        private readonly string $taxonomy,
        private readonly array $termIds,
    ) {
    }

    public function apply(PostQueryBuilder $builder): PostQueryBuilder
    {
        return $builder->inTaxonomyByIds($this->taxonomy, $this->termIds);
    }
}
