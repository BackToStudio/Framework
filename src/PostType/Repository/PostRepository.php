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
}
