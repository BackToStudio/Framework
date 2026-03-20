<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Contracts;

/**
 * Port for executing raw post queries against the persistence layer.
 *
 * This gateway abstracts the underlying data source (WordPress, test doubles, etc.)
 * so that the PostQueryBuilder remains free of infrastructure concerns.
 */
interface PostQueryGatewayInterface
{
    /**
     * @param array<string, mixed> $args
     * @return \WP_Post[]
     */
    public function queryPosts(array $args): array;
}
