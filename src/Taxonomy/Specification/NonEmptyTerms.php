<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Specification;

use BackTo\Framework\Taxonomy\Repository\TermQueryBuilder;

final class NonEmptyTerms implements TermSpecification
{
    public function apply(TermQueryBuilder $builder): TermQueryBuilder
    {
        return $builder->hideEmpty(true);
    }
}
