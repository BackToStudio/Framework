<?php

namespace BackTo\Framework\Taxonomy;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;

class TaxonomyRegistry implements RegistryInterface
{

    /**
     * @var TaxonomyInterface[]
     */
    private $taxonomies = [];

    /**
     * @param TaxonomyInterface $postType
     *
     * @return TaxonomyRegistry
     */
    public function add(TaxonomyInterface $postType): TaxonomyRegistry
    {
        $this->taxonomies[] = $postType;

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
