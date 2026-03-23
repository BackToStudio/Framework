<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Infrastructure;

use BackTo\Framework\Exception\PostNotFoundException;
use BackTo\Framework\PostType\Contracts\PostInterface;
use BackTo\Framework\PostType\Contracts\PostQueryGatewayInterface;
use BackTo\Framework\PostType\Contracts\PostRepositoryInterface;
use BackTo\Framework\PostType\Factory\PostFactory;
use BackTo\Framework\PostType\Repository\PostQueryBuilder;
use BackTo\Framework\Query\SortDirection;

use function get_post;

class WordPressPostRepository implements PostRepositoryInterface
{
    protected readonly PostFactory $factory;
    protected readonly PostQueryGatewayInterface $gateway;

    public function __construct(PostFactory $factory, PostQueryGatewayInterface $gateway)
    {
        $this->factory = $factory;
        $this->gateway = $gateway;
    }

    public function find(int $id): PostInterface
    {
        $wpPost = get_post($id);

        if ($wpPost === null) {
            throw PostNotFoundException::withId($id);
        }

        return $this->factory->create($wpPost);
    }

    /**
     * @param array<string, mixed> $args
     * @return PostInterface[]
     */
    public function findAll(array $args = []): array
    {
        $defaultArgs = [
            'numberposts' => 100,
        ];
        $wpPosts = $this->gateway->queryPosts(array_merge($defaultArgs, $args));

        return $this->factory->createFromPosts($wpPosts);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return PostInterface[]
     */
    public function findBy(array $criteria, ?string $orderBy = null, SortDirection $order = SortDirection::DESC, ?int $limit = null): array
    {
        return $this->query()
            ->postType($criteria['post_type'] ?? 'post')
            ->status($criteria['post_status'] ?? 'publish')
            ->orderBy($orderBy ?? 'date', $order)
            ->limit($limit ?? -1)
            ->get();
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function findOneBy(array $criteria): ?PostInterface
    {
        $results = $this->findBy($criteria);

        return $results[0] ?? null;
    }

    public function query(): PostQueryBuilder
    {
        return new PostQueryBuilder($this->factory, $this->gateway);
    }
}
