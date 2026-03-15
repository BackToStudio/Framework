<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Repository;

use BackTo\Framework\Exception\TermNotFoundException;
use BackTo\Framework\PostType\Repository\SortDirection;
use BackTo\Framework\Taxonomy\Contracts\TermInterface;
use BackTo\Framework\Taxonomy\Factory\TermFactory;
use WP_Term;

use function get_term;
use function get_terms;

class TermRepository
{
    protected TermFactory $factory;

    public function __construct(TermFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Get all Term data from database by Term ID.
     *
     * @throws TermNotFoundException
     */
    public function find(int $id, string $taxonomy = 'category'): TermInterface
    {
        $wpTerm = get_term($id, $taxonomy);

        if (!$wpTerm instanceof WP_Term) {
            throw TermNotFoundException::withId($id, $taxonomy);
        }

        return $this->factory->create($wpTerm);
    }

    /**
     * Retrieves the terms in a given taxonomy or list of taxonomies.
     *
     * @param array<string, mixed> $options
     * @return TermInterface[]
     */
    public function findAll(array $options = []): array
    {
        $wpTerms = get_terms(
            array_merge(
                [
                    'taxonomy' => 'category',
                    'hide_empty' => false,
                ],
                $options
            )
        );

        if ($wpTerms instanceof \WP_Error) {
            return [];
        }

        return $this->factory->createFromTerms($wpTerms);
    }

    /**
     * @return TermInterface[]
     */
    public function findBy(string $taxonomy, ?string $orderBy = null, SortDirection $order = SortDirection::ASC, ?int $limit = null): array
    {
        return $this->query()
            ->taxonomy($taxonomy)
            ->orderBy($orderBy ?? 'name', $order)
            ->limit($limit ?? 0)
            ->get();
    }

    public function findOneBySlug(string $slug, string $taxonomy): ?TermInterface
    {
        return $this->query()
            ->taxonomy($taxonomy)
            ->slug($slug)
            ->first();
    }

    public function query(): TermQueryBuilder
    {
        return new TermQueryBuilder($this->factory);
    }
}
