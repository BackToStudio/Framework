<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Infrastructure;

use BackTo\Framework\Exception\TermNotFoundException;
use BackTo\Framework\Query\SortDirection;
use BackTo\Framework\Taxonomy\Contracts\TermInterface;
use BackTo\Framework\Taxonomy\Contracts\TermQueryGatewayInterface;
use BackTo\Framework\Taxonomy\Contracts\TermRepositoryInterface;
use BackTo\Framework\Taxonomy\Factory\TermFactory;
use BackTo\Framework\Taxonomy\Repository\TermQueryBuilder;
use WP_Term;

use function get_term;

class WordPressTermRepository implements TermRepositoryInterface
{
    protected readonly TermFactory $factory;
    protected readonly TermQueryGatewayInterface $gateway;

    public function __construct(TermFactory $factory, TermQueryGatewayInterface $gateway)
    {
        $this->factory = $factory;
        $this->gateway = $gateway;
    }

    public function find(int $id, string $taxonomy = 'category'): TermInterface
    {
        $wpTerm = get_term($id, $taxonomy);

        if (!$wpTerm instanceof WP_Term) {
            throw TermNotFoundException::withId($id, $taxonomy);
        }

        return $this->factory->create($wpTerm);
    }

    /**
     * @param array<string, mixed> $options
     * @return TermInterface[]
     */
    public function findAll(array $options = []): array
    {
        $wpTerms = $this->gateway->queryTerms(
            array_merge(
                [
                    'taxonomy' => 'category',
                    'hide_empty' => false,
                ],
                $options
            )
        );

        return $this->factory->createFromTerms($wpTerms);
    }

    /**
     * @return TermInterface[]
     */
    public function findBy(string $taxonomy, ?string $orderBy = null, SortDirection $order = SortDirection::ASC, ?int $limit = null): array
    {
        return $this->query()
            ->taxonomy($taxonomy)
            ->orderBy($orderBy ?? 'name', $order)
            ->limit($limit ?? 0)
            ->get();
    }

    public function findOneBySlug(string $slug, string $taxonomy): ?TermInterface
    {
        return $this->query()
            ->taxonomy($taxonomy)
            ->slug($slug)
            ->first();
    }

    public function query(): TermQueryBuilder
    {
        return new TermQueryBuilder($this->factory, $this->gateway);
    }
}
