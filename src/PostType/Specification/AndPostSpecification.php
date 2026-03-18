<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Specification;

use BackTo\Framework\PostType\Repository\PostQueryBuilder;

final class AndPostSpecification implements PostSpecification
{
    /** @var PostSpecification[] */
    private array $specifications;

    public function __construct(PostSpecification ...$specifications)
    {
        $this->specifications = $specifications;
    }

    public function apply(PostQueryBuilder $builder): PostQueryBuilder
    {
        foreach ($this->specifications as $specification) {
            $builder = $specification->apply($builder);
        }

        return $builder;
    }
}
