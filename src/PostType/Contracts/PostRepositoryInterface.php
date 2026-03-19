<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Contracts;

/**
 * Port for retrieving and querying posts.
 */
interface PostRepositoryInterface
{
    /**
     * Retrieves post data given a post ID.
     *
     * @throws \BackTo\Framework\Exception\PostNotFoundException
     */
    public function find(int $id): PostInterface;

    /**
     * @param array<string, mixed> $args
     * @return PostInterface[]
     */
    public function findAll(array $args = []): array;

    /**
     * @param array<string, mixed> $criteria
     * @return PostInterface[]
     */
    public function findBy(array $criteria, ?string $orderBy = null, \BackTo\Framework\Query\SortDirection $order = \BackTo\Framework\Query\SortDirection::DESC, ?int $limit = null): array;

    /**
     * @param array<string, mixed> $criteria
     */
    public function findOneBy(array $criteria): ?PostInterface;
}
