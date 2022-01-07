<?php

namespace Fantassin\Core\WordPress\Taxonomy\Repository;

use Fantassin\Core\WordPress\Taxonomy\Contracts\TermInterface;
use Fantassin\Core\WordPress\Taxonomy\Factory\TermFactory;

use function get_term;
use function get_terms;

class TermRepository
{
    /**
     * @var TermFactory
     */
    protected $factory;

    public function __construct(TermFactory $factory){
        $this->factory = $factory;
    }

    /**
     * Get all Term data from database by Term ID.
     *
     * @param int $id
     * @param string $taxonomy
     * @return TermInterface
     */
    public function find(int $id, $taxonomy = 'category'): TermInterface
    {
        $wpTerm = get_term($id, $taxonomy);
        return $this->factory->create($wpTerm);
    }

    /**
     * Retrieves the terms in a given taxonomy or list of taxonomies.
     *
     * @param array $options
     * @return TermInterface[]
     */
    public function findAll(array $options = []): array
    {
        $wpTerms = get_terms(
            array_merge(
                array(
                    'taxonomy' => 'category',
                    'hide_empty' => false,
                ),
                $options
            )
        );
        return $this->factory->createFromTerms($wpTerms);
    }
}
