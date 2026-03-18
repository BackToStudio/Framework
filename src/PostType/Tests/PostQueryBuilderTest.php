<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\PostType\Factory\PostFactory;
use BackTo\Framework\PostType\Repository\MetaCompare;
use BackTo\Framework\PostType\Repository\PostQueryBuilder;
use BackTo\Framework\PostType\Repository\SortDirection;
use PHPUnit\Framework\TestCase;

class PostQueryBuilderTest extends TestCase
{
    private function createQueryBuilder(): PostQueryBuilder
    {
        $factory = $this->createMock(PostFactory::class);
        return new PostQueryBuilder($factory);
    }

    public function testPostType(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->postType('page');
        $this->assertSame('page', $qb->getArgs()['post_type']);
    }

    public function testStatus(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->status('draft');
        $this->assertSame('draft', $qb->getArgs()['post_status']);
    }

    public function testStatuses(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->statuses(['publish', 'draft']);
        $this->assertSame(['publish', 'draft'], $qb->getArgs()['post_status']);
    }

    public function testLimit(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->limit(10);
        $this->assertSame(10, $qb->getArgs()['numberposts']);
    }

    public function testOffset(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->offset(5);
        $this->assertSame(5, $qb->getArgs()['offset']);
    }

    public function testPage(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->page(3, 20);
        $this->assertSame(20, $qb->getArgs()['posts_per_page']);
        $this->assertSame(3, $qb->getArgs()['paged']);
    }

    public function testOrderBy(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->orderBy('title', SortDirection::ASC);
        $this->assertSame('title', $qb->getArgs()['orderby']);
        $this->assertSame('ASC', $qb->getArgs()['order']);
    }

    public function testOrderByDefaultsToDesc(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->orderBy('date');
        $this->assertSame('DESC', $qb->getArgs()['order']);
    }

    public function testAuthor(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->author(42);
        $this->assertSame(42, $qb->getArgs()['author']);
    }

    public function testParent(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->parent(10);
        $this->assertSame(10, $qb->getArgs()['post_parent']);
    }

    public function testSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->search('hello');
        $this->assertSame('hello', $qb->getArgs()['s']);
    }

    public function testWhereIn(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereIn([1, 2, 3]);
        $this->assertSame([1, 2, 3], $qb->getArgs()['post__in']);
    }

    public function testWhereNotIn(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereNotIn([4, 5]);
        $this->assertSame([4, 5], $qb->getArgs()['post__not_in']);
    }

    public function testWhereMeta(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereMeta('color', 'red');
        $this->assertCount(1, $qb->getArgs()['meta_query']);
        $this->assertSame('color', $qb->getArgs()['meta_query'][0]['key']);
        $this->assertSame('red', $qb->getArgs()['meta_query'][0]['value']);
        $this->assertSame('=', $qb->getArgs()['meta_query'][0]['compare']);
    }

    public function testWhereMetaWithCompare(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereMeta('price', 100, MetaCompare::GREATER_THAN);
        $this->assertSame('>', $qb->getArgs()['meta_query'][0]['compare']);
    }

    public function testWhereMetaExists(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereMetaExists('featured');
        $this->assertSame('EXISTS', $qb->getArgs()['meta_query'][0]['compare']);
    }

    public function testWhereMetaNotExists(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereMetaNotExists('deprecated_field');
        $this->assertSame('NOT EXISTS', $qb->getArgs()['meta_query'][0]['compare']);
    }

    public function testInTaxonomyByIds(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->inTaxonomyByIds('category', [1, 2]);
        $this->assertCount(1, $qb->getArgs()['tax_query']);
        $this->assertSame('category', $qb->getArgs()['tax_query'][0]['taxonomy']);
        $this->assertSame('term_id', $qb->getArgs()['tax_query'][0]['field']);
        $this->assertSame([1, 2], $qb->getArgs()['tax_query'][0]['terms']);
    }

    public function testInTaxonomyBySlugs(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->inTaxonomyBySlugs('category', ['tech', 'science']);
        $this->assertSame('slug', $qb->getArgs()['tax_query'][0]['field']);
        $this->assertSame(['tech', 'science'], $qb->getArgs()['tax_query'][0]['terms']);
    }

    public function testInTaxonomyByNames(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->inTaxonomyByNames('post_tag', ['PHP', 'WordPress']);
        $this->assertSame('name', $qb->getArgs()['tax_query'][0]['field']);
        $this->assertSame(['PHP', 'WordPress'], $qb->getArgs()['tax_query'][0]['terms']);
    }

    public function testFluentChaining(): void
    {
        $qb = $this->createQueryBuilder();
        $result = $qb
            ->postType('product')
            ->status('publish')
            ->limit(10)
            ->orderBy('date', SortDirection::DESC)
            ->whereMeta('price', '100', MetaCompare::GREATER_THAN);

        $this->assertInstanceOf(PostQueryBuilder::class, $result);
        $args = $qb->getArgs();
        $this->assertSame('product', $args['post_type']);
        $this->assertSame('publish', $args['post_status']);
        $this->assertSame(10, $args['numberposts']);
        $this->assertSame('date', $args['orderby']);
    }

    public function testMultipleMetaQueries(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereMeta('color', 'red')
           ->whereMeta('size', 'large')
           ->whereMetaExists('featured');

        $this->assertCount(3, $qb->getArgs()['meta_query']);
    }

    public function testEmptyArgsInitially(): void
    {
        $qb = $this->createQueryBuilder();
        $this->assertEmpty($qb->getArgs());
    }

    public function testWhereInWithEmptyArrayUsesImpossibleId(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereIn([]);

        // Empty post__in returns all posts in WordPress.
        // Should use [0] to guarantee no results.
        $this->assertSame([0], $qb->getArgs()['post__in']);
    }

    public function testWhereNotInWithEmptyArrayIsNoOp(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereNotIn([]);

        // Empty post__not_in should not be set (no exclusions needed).
        $this->assertArrayNotHasKey('post__not_in', $qb->getArgs());
    }

    public function testWhereInWithNonEmptyArrayPassesThrough(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereIn([5, 10, 15]);
        $this->assertSame([5, 10, 15], $qb->getArgs()['post__in']);
    }

    public function testLimitThenPageOverridesNumberposts(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->limit(50)->page(2, 10);

        $args = $qb->getArgs();
        // page() sets posts_per_page, but limit() set numberposts — both present
        $this->assertSame(50, $args['numberposts']);
        $this->assertSame(10, $args['posts_per_page']);
        $this->assertSame(2, $args['paged']);
    }

    public function testPageThenLimitOverridesPerPage(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->page(1, 20)->limit(5);

        $args = $qb->getArgs();
        // Both are set; limit() overwrites numberposts
        $this->assertSame(5, $args['numberposts']);
        $this->assertSame(20, $args['posts_per_page']);
    }
}
