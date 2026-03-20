<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Repository;

use BackTo\Framework\PostMeta\ValueObject\MetaKey;
use BackTo\Framework\PostType\Contracts\PostInterface;
use BackTo\Framework\PostType\Entity\PostStatus;
use BackTo\Framework\PostType\Factory\PostFactory;
use BackTo\Framework\PostType\Specification\PostSpecification;
use BackTo\Framework\Query\MetaCompare;
use BackTo\Framework\Query\SortDirection;

use function get_posts;

final class PostQueryBuilder
{
    /** @var array<string, mixed> */
    private array $args = [];

    private readonly PostFactory $factory;

    public function __construct(PostFactory $factory)
    {
        $this->factory = $factory;
    }

    public function matching(PostSpecification $specification): self
    {
        return $specification->apply($this);
    }

    public function postType(string $postType): self
    {
        $this->args['post_type'] = $postType;
        return $this;
    }

    public function status(PostStatus|string $status): self
    {
        $this->args['post_status'] = $status instanceof PostStatus ? $status->value : $status;
        return $this;
    }

    /**
     * @param array<PostStatus|string> $statuses
     */
    public function statuses(array $statuses): self
    {
        $this->args['post_status'] = array_map(
            static fn (PostStatus|string $s): string => $s instanceof PostStatus ? $s->value : $s,
            $statuses,
        );
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

    public function orderBy(string $field, SortDirection $direction = SortDirection::DESC): self
    {
        $this->args['orderby'] = $field;
        $this->args['order'] = $direction->value;
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

    
    public function whereIn(array $ids): self
    {
        // WordPress returns all posts when post__in is empty.
        // Force no results by using an impossible ID.
        $this->args['post__in'] = $ids === [] ? [0] : $ids;
        return $this;
    }


    public function whereNotIn(array $ids): self
    {
        if ($ids === []) {
            return $this;
        }

        $this->args['post__not_in'] = $ids;
        return $this;
    }

    public function whereMeta(MetaKey|string $key, mixed $value, MetaCompare $compare = MetaCompare::EQUAL): self
    {
        return $this->addMetaQuery([
            'key' => (string) $key,
            'value' => $value,
            'compare' => $compare->value,
        ]);
    }

    public function whereMetaExists(MetaKey|string $key): self
    {
        return $this->addMetaQuery([
            'key' => (string) $key,
            'compare' => MetaCompare::EXISTS->value,
        ]);
    }

    public function whereMetaNotExists(MetaKey|string $key): self
    {
        return $this->addMetaQuery([
            'key' => (string) $key,
            'compare' => MetaCompare::NOT_EXISTS->value,
        ]);
    }

    
    public function inTaxonomyByIds(string $taxonomy, array $termIds): self
    {
        return $this->addTaxQuery($taxonomy, 'term_id', $termIds);
    }

    
    public function inTaxonomyBySlugs(string $taxonomy, array $slugs): self
    {
        return $this->addTaxQuery($taxonomy, 'slug', $slugs);
    }

    
    public function inTaxonomyByNames(string $taxonomy, array $names): self
    {
        return $this->addTaxQuery($taxonomy, 'name', $names);
    }

    
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

    /**
     * @param array<string, mixed> $clause
     */
    private function addMetaQuery(array $clause): self
    {
        if (!isset($this->args['meta_query'])) {
            $this->args['meta_query'] = [];
        }

        $this->args['meta_query'][] = $clause;

        return $this;
    }


    private function addTaxQuery(string $taxonomy, string $field, array $terms): self
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
}
