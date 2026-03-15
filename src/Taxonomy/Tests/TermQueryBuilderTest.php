<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Tests;

use BackTo\Framework\PostType\Repository\MetaCompare;
use BackTo\Framework\PostType\Repository\SortDirection;
use BackTo\Framework\Taxonomy\Factory\TermFactory;
use BackTo\Framework\Taxonomy\Repository\TermQueryBuilder;
use PHPUnit\Framework\TestCase;

class TermQueryBuilderTest extends TestCase
{
    private function createQueryBuilder(): TermQueryBuilder
    {
        $factory = $this->createMock(TermFactory::class);
        return new TermQueryBuilder($factory);
    }

    public function testTaxonomy(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->taxonomy('category');
        $this->assertSame('category', $qb->getArgs()['taxonomy']);
    }

    public function testTaxonomies(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->taxonomies(['category', 'post_tag']);
        $this->assertSame(['category', 'post_tag'], $qb->getArgs()['taxonomy']);
    }

    public function testHideEmpty(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->hideEmpty(true);
        $this->assertTrue($qb->getArgs()['hide_empty']);
    }

    public function testLimit(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->limit(5);
        $this->assertSame(5, $qb->getArgs()['number']);
    }

    public function testOffset(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->offset(10);
        $this->assertSame(10, $qb->getArgs()['offset']);
    }

    public function testOrderBy(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->orderBy('count', SortDirection::DESC);
        $this->assertSame('count', $qb->getArgs()['orderby']);
        $this->assertSame('DESC', $qb->getArgs()['order']);
    }

    public function testOrderByDefaultsToAsc(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->orderBy('name');
        $this->assertSame('ASC', $qb->getArgs()['order']);
    }

    public function testParent(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->parent(5);
        $this->assertSame(5, $qb->getArgs()['parent']);
    }

    public function testChildOf(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->childOf(3);
        $this->assertSame(3, $qb->getArgs()['child_of']);
    }

    public function testSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->search('tech');
        $this->assertSame('tech', $qb->getArgs()['search']);
    }

    public function testWhereIn(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereIn([1, 2, 3]);
        $this->assertSame([1, 2, 3], $qb->getArgs()['include']);
    }

    public function testWhereNotIn(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereNotIn([4, 5]);
        $this->assertSame([4, 5], $qb->getArgs()['exclude']);
    }

    public function testSlug(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->slug('my-term');
        $this->assertSame('my-term', $qb->getArgs()['slug']);
    }

    public function testWhereMeta(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereMeta('icon', 'star', MetaCompare::LIKE);
        $this->assertCount(1, $qb->getArgs()['meta_query']);
        $this->assertSame('icon', $qb->getArgs()['meta_query'][0]['key']);
        $this->assertSame('star', $qb->getArgs()['meta_query'][0]['value']);
        $this->assertSame('LIKE', $qb->getArgs()['meta_query'][0]['compare']);
    }

    public function testWhereMetaExists(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->whereMetaExists('featured');
        $this->assertSame('EXISTS', $qb->getArgs()['meta_query'][0]['compare']);
    }

    public function testFluentChaining(): void
    {
        $qb = $this->createQueryBuilder();
        $result = $qb
            ->taxonomy('product_cat')
            ->hideEmpty()
            ->limit(20)
            ->orderBy('name', SortDirection::ASC);

        $this->assertInstanceOf(TermQueryBuilder::class, $result);
        $args = $qb->getArgs();
        $this->assertSame('product_cat', $args['taxonomy']);
        $this->assertTrue($args['hide_empty']);
        $this->assertSame(20, $args['number']);
    }

    public function testEmptyArgsInitially(): void
    {
        $qb = $this->createQueryBuilder();
        $this->assertEmpty($qb->getArgs());
    }
}
