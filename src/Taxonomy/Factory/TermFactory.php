<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Factory;

use DateTimeImmutable;
use Exception;
use BackTo\Framework\Taxonomy\Contracts\TermInterface;
use BackTo\Framework\Taxonomy\Entity\Term;
use WP_Term;

class TermFactory
{

    public function create(WP_Term $wpTerm): TermInterface
    {
        $term = new Term();
        $term->setId($wpTerm->term_id);
        $term->setName($wpTerm->name);
        $term->setDescription($wpTerm->description);
        $term->setTaxonomy($wpTerm->taxonomy);
        $term->setSlug($wpTerm->slug);
        $term->setParentId($wpTerm->parent);

        return $term;
    }

    /**
     * @param WP_Term[] $wpTerms
     *
     * @return TermInterface[]
     */
    public function createFromTerms(array $wpTerms): array
    {
        return array_map(
            function (WP_Term $wpPost) {
                return $this->create($wpPost);
            },
            $wpTerms
        );
    }
}
