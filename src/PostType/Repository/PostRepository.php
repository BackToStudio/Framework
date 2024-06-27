<?php

namespace BackTo\Framework\PostType\Repository;

use BackTo\Framework\PostType\Contracts\PostInterface;
use BackTo\Framework\PostType\Factory\PostFactory;

use function get_post;

class PostRepository
{

    /**
     * @var PostFactory
     */
    protected $factory;

    public function __construct(PostFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Retrieves post data given a post ID.
     *
     * @param int $id
     * @return PostInterface
     */
    public function find(int $id): PostInterface
    {
        $wpPost = get_post($id);
        return $this->factory->create($wpPost);
    }

    /**
     * @param $args
     *
     * @return PostInterface[]
     */
    public function findAll($args = []): array
    {
        $defaultArgs = [
            'numberposts' => -1
        ];
        $wpPosts = get_posts(array_merge($defaultArgs, $args));
        return $this->factory->createFromPosts($wpPosts);
    }

}
