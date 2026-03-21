<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\PostMeta\ValueObject\MetaKey;
use BackTo\Framework\PostType\Contracts\PostQueryGatewayInterface;
use BackTo\Framework\PostType\Entity\PostStatus;
use BackTo\Framework\PostType\Factory\PostFactory;
use BackTo\Framework\Query\MetaCompare;
use BackTo\Framework\PostType\Repository\PostQueryBuilder;
use BackTo\Framework\PostType\Specification\AndPostSpecification;
use BackTo\Framework\PostType\Specification\PostsByAuthor;
use BackTo\Framework\PostType\Specification\PostsByStatus;
use BackTo\Framework\PostType\Specification\PostsByType;
use BackTo\Framework\PostType\Specification\PostsInTaxonomy;
use BackTo\Framework\PostType\Specification\PostsWithMeta;
use BackTo\Framework\PostType\Specification\PublishedPosts;
use BackTo\Framework\PostType\Specification\RecentPosts;
use PHPUnit\Framework\TestCase;

class PostSpecificationTest extends TestCase
{
    private function createQueryBuilder(): PostQueryBuilder
    {
        $logger = $this->createMock(LoggerInterface::class);
        return new PostQueryBuilder(
            new PostFactory($logger),
            $this->createMock(PostQueryGatewayInterface::class),
        );
    }

    // --- PublishedPosts ---

    public function testPublishedPosts(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new PublishedPosts());

        $this->assertSame('publish', $qb->getArgs()['post_status']);
    }

    // --- PostsByStatus ---

    public function testPostsByStatus(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new PostsByStatus(PostStatus::Draft));

        $this->assertSame('draft', $qb->getArgs()['post_status']);
    }

    // --- PostsByAuthor ---

    public function testPostsByAuthor(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new PostsByAuthor(42));

        $this->assertSame(42, $qb->getArgs()['author']);
    }

    // --- PostsByType ---

    public function testPostsByType(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new PostsByType('page'));

        $this->assertSame('page', $qb->getArgs()['post_type']);
    }

    // --- PostsWithMeta ---

    public function testPostsWithMetaExists(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new PostsWithMeta('featured'));

        $this->assertSame('EXISTS', $qb->getArgs()['meta_query'][0]['compare']);
        $this->assertSame('featured', $qb->getArgs()['meta_query'][0]['key']);
    }

    public function testPostsWithMetaValue(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new PostsWithMeta('color', 'red', MetaCompare::EQUAL));

        $this->assertSame('color', $qb->getArgs()['meta_query'][0]['key']);
        $this->assertSame('red', $qb->getArgs()['meta_query'][0]['value']);
        $this->assertSame('=', $qb->getArgs()['meta_query'][0]['compare']);
    }

    public function testPostsWithMetaKeyValueObject(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new PostsWithMeta(new MetaKey('_price'), 100, MetaCompare::GREATER_THAN));

        $this->assertSame('_price', $qb->getArgs()['meta_query'][0]['key']);
        $this->assertSame('>', $qb->getArgs()['meta_query'][0]['compare']);
    }

    // --- PostsInTaxonomy ---

    public function testPostsInTaxonomy(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new PostsInTaxonomy('category', [1, 5, 9]));

        $this->assertSame('category', $qb->getArgs()['tax_query'][0]['taxonomy']);
        $this->assertSame('term_id', $qb->getArgs()['tax_query'][0]['field']);
        $this->assertSame([1, 5, 9], $qb->getArgs()['tax_query'][0]['terms']);
    }

    // --- RecentPosts ---

    public function testRecentPosts(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new RecentPosts(5));

        $this->assertSame('publish', $qb->getArgs()['post_status']);
        $this->assertSame('date', $qb->getArgs()['orderby']);
        $this->assertSame('DESC', $qb->getArgs()['order']);
        $this->assertSame(5, $qb->getArgs()['numberposts']);
    }

    public function testRecentPostsDefaultLimit(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new RecentPosts());

        $this->assertSame(10, $qb->getArgs()['numberposts']);
    }

    // --- AndPostSpecification ---

    public function testAndSpecificationCombinesMultiple(): void
    {
        $spec = new AndPostSpecification(
            new PostsByType('product'),
            new PublishedPosts(),
            new PostsByAuthor(7),
        );

        $qb = $this->createQueryBuilder();
        $qb->matching($spec);

        $args = $qb->getArgs();
        $this->assertSame('product', $args['post_type']);
        $this->assertSame('publish', $args['post_status']);
        $this->assertSame(7, $args['author']);
    }

    // --- matching() chaining ---

    public function testMatchingIsChainable(): void
    {
        $qb = $this->createQueryBuilder();
        $result = $qb
            ->matching(new PublishedPosts())
            ->matching(new PostsByType('page'))
            ->limit(5);

        $this->assertInstanceOf(PostQueryBuilder::class, $result);
        $this->assertSame('publish', $qb->getArgs()['post_status']);
        $this->assertSame('page', $qb->getArgs()['post_type']);
        $this->assertSame(5, $qb->getArgs()['numberposts']);
    }
}
