<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Tests;

use BackTo\Framework\Taxonomy\Contracts\TermQueryGatewayInterface;
use BackTo\Framework\Taxonomy\Factory\TermFactory;
use BackTo\Framework\Taxonomy\Repository\TermQueryBuilder;
use BackTo\Framework\Taxonomy\Specification\AndTermSpecification;
use BackTo\Framework\Taxonomy\Specification\NonEmptyTerms;
use BackTo\Framework\Taxonomy\Specification\TermsInTaxonomy;
use BackTo\Framework\Taxonomy\Specification\TopLevelTerms;
use PHPUnit\Framework\TestCase;

class TermSpecificationTest extends TestCase
{
    private function createQueryBuilder(): TermQueryBuilder
    {
        return new TermQueryBuilder(
            $this->createMock(TermFactory::class),
            $this->createMock(TermQueryGatewayInterface::class),
        );
    }

    // --- TermsInTaxonomy ---

    public function testTermsInTaxonomy(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new TermsInTaxonomy('category'));

        $this->assertSame('category', $qb->getArgs()['taxonomy']);
    }

    // --- NonEmptyTerms ---

    public function testNonEmptyTerms(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new NonEmptyTerms());

        $this->assertTrue($qb->getArgs()['hide_empty']);
    }

    // --- TopLevelTerms ---

    public function testTopLevelTerms(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->matching(new TopLevelTerms());

        $this->assertSame(0, $qb->getArgs()['parent']);
    }

    // --- AndTermSpecification ---

    public function testAndSpecificationCombinesMultiple(): void
    {
        $spec = new AndTermSpecification(
            new TermsInTaxonomy('product_cat'),
            new NonEmptyTerms(),
            new TopLevelTerms(),
        );

        $qb = $this->createQueryBuilder();
        $qb->matching($spec);

        $args = $qb->getArgs();
        $this->assertSame('product_cat', $args['taxonomy']);
        $this->assertTrue($args['hide_empty']);
        $this->assertSame(0, $args['parent']);
    }

    // --- matching() chaining ---

    public function testMatchingIsChainable(): void
    {
        $qb = $this->createQueryBuilder();
        $result = $qb
            ->matching(new TermsInTaxonomy('category'))
            ->matching(new NonEmptyTerms())
            ->limit(10);

        $this->assertInstanceOf(TermQueryBuilder::class, $result);
        $this->assertSame('category', $qb->getArgs()['taxonomy']);
        $this->assertTrue($qb->getArgs()['hide_empty']);
        $this->assertSame(10, $qb->getArgs()['number']);
    }
}
