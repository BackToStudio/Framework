<?php

namespace BackTo\Framework\PostMeta\Repository;

use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;
use BackTo\Framework\PostMeta\Factory\PostMetaFactory;

class PostMetaRepository
{
    /**
     * @var PostMetaFactory
     */
    private $factory;

    public function __construct(PostMetaFactory $factory = null)
    {
        $this->factory = $factory ?? new PostMetaFactory();
    }

    /**
     * Create a new post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function create(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        return $this->factory->create($key, $label, $postTypes, $args);
    }

    /**
     * Get a post meta value
     *
     * @param int $postId
     * @param string $key
     * @param bool $single
     * @return mixed
     */
    public function get(int $postId, string $key, bool $single = true)
    {
        return get_post_meta($postId, $key, $single);
    }

    /**
     * Update a post meta value
     *
     * @param int $postId
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function update(int $postId, string $key, $value)
    {
        return update_post_meta($postId, $key, $value);
    }

    /**
     * Delete a post meta
     *
     * @param int $postId
     * @param string $key
     * @return bool
     */
    public function delete(int $postId, string $key): bool
    {
        return delete_post_meta($postId, $key);
    }

    /**
     * Get all post meta values for a post
     *
     * @param int $postId
     * @return array
     */
    public function all(int $postId): array
    {
        return get_post_meta($postId);
    }

    /**
     * Get all registered post meta values for a post
     *
     * @param int $postId
     * @return array
     */
    public function allRegistered(int $postId): array
    {
        $result = [];
        foreach (PostMetaRegistry::all() as $key => $postMeta) {
            $result[$key] = $this->get($postId, $key);
        }
        return $result;
    }

    /**
     * Get post meta values for multiple posts
     *
     * @param array $postIds
     * @param string $key
     * @param bool $single
     * @return array
     */
    public function getForPosts(array $postIds, string $key, bool $single = true): array
    {
        $result = [];
        foreach ($postIds as $postId) {
            $result[$postId] = $this->get($postId, $key, $single);
        }
        return $result;
    }

    /**
     * Create a text post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createText(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        return $this->factory->createText($key, $label, $postTypes, $args);
    }

    /**
     * Create a textarea post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createTextarea(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        return $this->factory->createTextarea($key, $label, $postTypes, $args);
    }

    /**
     * Create a checkbox post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createCheckbox(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        return $this->factory->createCheckbox($key, $label, $postTypes, $args);
    }

    /**
     * Create a number post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createNumber(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        return $this->factory->createNumber($key, $label, $postTypes, $args);
    }

    /**
     * Create a date post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createDate(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        return $this->factory->createDate($key, $label, $postTypes, $args);
    }

    /**
     * Create a datetime post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createDatetime(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        return $this->factory->createDatetime($key, $label, $postTypes, $args);
    }
} 