<?php

namespace Fantassin\Core\WordPress\Taxonomy;

use Fantassin\Core\WordPress\Contracts\RegistryInterface;
use Fantassin\Core\WordPress\Taxonomy\Contracts\TaxonomyInterface;

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
