<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Specification;

use BackTo\Framework\Taxonomy\Repository\TermQueryBuilder;

final class AndTermSpecification implements TermSpecification
{
    /** @var TermSpecification[] */
    private array $specifications;

    public function __construct(TermSpecification ...$specifications)
    {
        $this->specifications = $specifications;
    }

    public function apply(TermQueryBuilder $builder): TermQueryBuilder
    {
        foreach ($this->specifications as $specification) {
            $builder = $specification->apply($builder);
        }

        return $builder;
    }
}
