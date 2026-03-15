<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Repository;

use BackTo\Framework\Taxonomy\Contracts\TermInterface;
use BackTo\Framework\Taxonomy\Factory\TermFactory;

use function get_terms;

class TermQueryBuilder
{
    /** @var array<string, mixed> */
    private array $args = [];

    private TermFactory $factory;

    public function __construct(TermFactory $factory)
    {
        $this->factory = $factory;
    }

    public function taxonomy(string $taxonomy): self
    {
        $this->args['taxonomy'] = $taxonomy;
        return $this;
    }

    /**
     * @param string[] $taxonomies
     */
    public function taxonomies(array $taxonomies): self
    {
        $this->args['taxonomy'] = $taxonomies;
        return $this;
    }

    public function hideEmpty(bool $hideEmpty = true): self
    {
        $this->args['hide_empty'] = $hideEmpty;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->args['number'] = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->args['offset'] = $offset;
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->args['orderby'] = $field;
        $this->args['order'] = $direction;
        return $this;
    }

    public function parent(int $parentId): self
    {
        $this->args['parent'] = $parentId;
        return $this;
    }

    public function childOf(int $termId): self
    {
        $this->args['child_of'] = $termId;
        return $this;
    }

    public function search(string $query): self
    {
        $this->args['search'] = $query;
        return $this;
    }

    /**
     * @param int[] $ids
     */
    public function whereIn(array $ids): self
    {
        $this->args['include'] = $ids;
        return $this;
    }

    /**
     * @param int[] $ids
     */
    public function whereNotIn(array $ids): self
    {
        $this->args['exclude'] = $ids;
        return $this;
    }

    public function slug(string $slug): self
    {
        $this->args['slug'] = $slug;
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

    /**
     * @return TermInterface[]
     */
    public function get(): array
    {
        $defaults = [
            'hide_empty' => false,
        ];

        $wpTerms = get_terms(array_merge($defaults, $this->args));

        return $this->factory->createFromTerms($wpTerms);
    }

    public function first(): ?TermInterface
    {
        $this->args['number'] = 1;
        $results = $this->get();

        return $results[0] ?? null;
    }

    public function count(): int
    {
        $this->args['fields'] = 'count';
        $defaults = [
            'hide_empty' => false,
        ];

        /** @var string|int $count */
        $count = get_terms(array_merge($defaults, $this->args));

        return (int) $count;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArgs(): array
    {
        return $this->args;
    }
}
