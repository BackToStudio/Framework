<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Repository;

use BackTo\Framework\Exception\PostNotFoundException;
use BackTo\Framework\PostType\Contracts\PostInterface;
use BackTo\Framework\PostType\Factory\PostFactory;

use function get_post;
use function get_posts;

class PostRepository
{
    protected PostFactory $factory;

    public function __construct(PostFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Retrieves post data given a post ID.
     *
     * @throws PostNotFoundException
     */
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
            'numberposts' => -1,
        ];
        $wpPosts = get_posts(array_merge($defaultArgs, $args));

        return $this->factory->createFromPosts($wpPosts);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return PostInterface[]
     */
    public function findBy(array $criteria, ?string $orderBy = null, string $order = 'DESC', ?int $limit = null): array
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
        $results = $this->findBy($criteria, null, 'DESC', 1);

        return $results[0] ?? null;
    }

    public function query(): PostQueryBuilder
    {
        return new PostQueryBuilder($this->factory);
    }
}
