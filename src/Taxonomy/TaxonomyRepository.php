<?php

namespace Fantassin\Core\WordPress\Taxonomy;

/**
 * @deprecated
 */
class TaxonomyRepository
{

    /**
     * @var CustomTaxonomy[]
     */
    private $taxonomies = [];

    /**
     * @param CustomTaxonomy $taxonomy
     *
     * @return TaxonomyRepository
     */
    public function add(CustomTaxonomy $taxonomy): TaxonomyRepository
    {
        $this->taxonomies[] = $taxonomy;

        return $this;
    }

    /**
     * @return CustomTaxonomy[]
     */
    public function getTaxonomies(): array
    {
        return $this->taxonomies;
    }
}
