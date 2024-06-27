<?php

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
        $term = (new Term())
            ->setId($wpTerm->term_id)
            ->setName($wpTerm->name)
            ->setDe($wpTerm->post_content)
            ->setSlug($wpTerm->slug)
            ->setParentId($wpTerm->parent);

        return $term;
    }

    /**
     * @param WP_Term[] $wpPosts
     * @return TermInterface[]
     */
    public function createFromTerms(array $wpTerms): array
    {
        return array_map(function (WP_Term $wpPost) {
            return $this->create($wpPost);
        });
    }

}
