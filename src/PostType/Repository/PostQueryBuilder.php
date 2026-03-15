<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Repository;

use BackTo\Framework\PostType\Contracts\PostInterface;
use BackTo\Framework\PostType\Factory\PostFactory;

use function get_posts;

class PostQueryBuilder
{
    /** @var array<string, mixed> */
    private array $args = [];

    private PostFactory $factory;

    public function __construct(PostFactory $factory)
    {
        $this->factory = $factory;
    }

    public function postType(string $postType): self
    {
        $this->args['post_type'] = $postType;
        return $this;
    }

    public function status(string $status): self
    {
        $this->args['post_status'] = $status;
        return $this;
    }

    /**
     * @param string[] $statuses
     */
    public function statuses(array $statuses): self
    {
        $this->args['post_status'] = $statuses;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->args['numberposts'] = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->args['offset'] = $offset;
        return $this;
    }

    public function page(int $page, int $perPage = 10): self
    {
        $this->args['posts_per_page'] = $perPage;
        $this->args['paged'] = $page;
        return $this;
    }

    public function orderBy(string $field, string $direction = 'DESC'): self
    {
        $this->args['orderby'] = $field;
        $this->args['order'] = $direction;
        return $this;
    }

    public function author(int $authorId): self
    {
        $this->args['author'] = $authorId;
        return $this;
    }

    public function parent(int $parentId): self
    {
        $this->args['post_parent'] = $parentId;
        return $this;
    }

    public function search(string $query): self
    {
        $this->args['s'] = $query;
        return $this;
    }

    /**
     * @param int[] $ids
     */
    public function whereIn(array $ids): self
    {
        $this->args['post__in'] = $ids;
        return $this;
    }

    /**
     * @param int[] $ids
     */
    public function whereNotIn(array $ids): self
    {
        $this->args['post__not_in'] = $ids;
        return $this;
    }

    public function whereMeta(string $key, mixed $value, string $compare = '='): self
    {
        if (!isset($this->args['meta_query'])) {
            $this->args['meta_query'] = [];
        }

        $this->args['meta_query'][] = [
            'key' => $key,
            'value' => $value,
            'compare' => $compare,
        ];

        return $this;
    }

    public function whereMetaExists(string $key): self
    {
        if (!isset($this->args['meta_query'])) {
            $this->args['meta_query'] = [];
        }

        $this->args['meta_query'][] = [
            'key' => $key,
            'compare' => 'EXISTS',
        ];

        return $this;
    }

    /**
     * @param int|string|array<int, int|string> $terms
     */
    public function inTaxonomy(string $taxonomy, int|string|array $terms, string $field = 'term_id'): self
    {
        if (!isset($this->args['tax_query'])) {
            $this->args['tax_query'] = [];
        }

        $this->args['tax_query'][] = [
            'taxonomy' => $taxonomy,
            'field' => $field,
            'terms' => $terms,
        ];

        return $this;
    }

    /**
     * @return PostInterface[]
     */
    public function get(): array
    {
        $defaults = [
            'numberposts' => -1,
            'post_status' => 'publish',
        ];

        $wpPosts = get_posts(array_merge($defaults, $this->args));

        return $this->factory->createFromPosts($wpPosts);
    }

    public function first(): ?PostInterface
    {
        $this->args['numberposts'] = 1;
        $results = $this->get();

        return $results[0] ?? null;
    }

    public function count(): int
    {
        $this->args['fields'] = 'ids';
        $defaults = [
            'numberposts' => -1,
            'post_status' => 'publish',
        ];

        $wpPosts = get_posts(array_merge($defaults, $this->args));

        return count($wpPosts);
    }

    /**
     * @return array<string, mixed>
     */
    public function getArgs(): array
    {
        return $this->args;
    }
}
