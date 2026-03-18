<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Specification;

use BackTo\Framework\Taxonomy\Repository\TermQueryBuilder;

final class TermsInTaxonomy implements TermSpecification
{
    public function __construct(
        private readonly string $taxonomy,
    ) {
    }

    public function apply(TermQueryBuilder $builder): TermQueryBuilder
    {
        return $builder->taxonomy($this->taxonomy);
    }
}
