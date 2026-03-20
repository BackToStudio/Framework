<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Contracts;

/**
 * Port for executing raw term queries against the persistence layer.
 *
 * This gateway abstracts the underlying data source (WordPress, test doubles, etc.)
 * so that the TermQueryBuilder remains free of infrastructure concerns.
 */
interface TermQueryGatewayInterface
{
    /**
     * @param array<string, mixed> $args
     * @return \WP_Term[]
     */
    public function queryTerms(array $args): array;

    /**
     * @param array<string, mixed> $args
     */
    public function countTerms(array $args): int;
}
