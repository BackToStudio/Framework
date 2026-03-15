<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;

class TaxonomyRegistry implements RegistryInterface
{
    /** @var TaxonomyInterface[] */
    private array $taxonomies = [];

    public function add(TaxonomyInterface $taxonomy): self
    {
        $this->taxonomies[] = $taxonomy;

        return $this;
    }

    /**
     * @return TaxonomyInterface[]
     */
    public function getTaxonomies(): array
    {
        return $this->taxonomies;
    }
}
