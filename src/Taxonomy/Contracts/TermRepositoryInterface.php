<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Contracts;

use BackTo\Framework\PostType\Repository\SortDirection;

/**
 * Port for retrieving and querying taxonomy terms.
 */
interface TermRepositoryInterface
{
    /**
     * Get all Term data from database by Term ID.
     *
     * @throws \BackTo\Framework\Exception\TermNotFoundException
     */
    public function find(int $id, string $taxonomy = 'category'): TermInterface;

    /**
     * Retrieves the terms in a given taxonomy or list of taxonomies.
     *
     * @param array<string, mixed> $options
     * @return TermInterface[]
     */
    public function findAll(array $options = []): array;

    /**
     * @return TermInterface[]
     */
    public function findBy(string $taxonomy, ?string $orderBy = null, SortDirection $order = SortDirection::ASC, ?int $limit = null): array;

    public function findOneBySlug(string $slug, string $taxonomy): ?TermInterface;
}
