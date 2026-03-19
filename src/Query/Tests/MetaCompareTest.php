<?php

declare(strict_types=1);

namespace BackTo\Framework\Query\Tests;

use BackTo\Framework\Query\MetaCompare;
use PHPUnit\Framework\TestCase;

class MetaCompareTest extends TestCase
{
    public function testComparisonOperatorValues(): void
    {
        $this->assertSame('=', MetaCompare::EQUAL->value);
        $this->assertSame('!=', MetaCompare::NOT_EQUAL->value);
        $this->assertSame('>', MetaCompare::GREATER_THAN->value);
        $this->assertSame('>=', MetaCompare::GREATER_THAN_OR_EQUAL->value);
        $this->assertSame('<', MetaCompare::LESS_THAN->value);
        $this->assertSame('<=', MetaCompare::LESS_THAN_OR_EQUAL->value);
    }

    public function testStringOperatorValues(): void
    {
        $this->assertSame('LIKE', MetaCompare::LIKE->value);
        $this->assertSame('NOT LIKE', MetaCompare::NOT_LIKE->value);
    }

    public function testSetOperatorValues(): void
    {
        $this->assertSame('IN', MetaCompare::IN->value);
        $this->assertSame('NOT IN', MetaCompare::NOT_IN->value);
        $this->assertSame('BETWEEN', MetaCompare::BETWEEN->value);
        $this->assertSame('NOT BETWEEN', MetaCompare::NOT_BETWEEN->value);
    }

    public function testExistenceOperatorValues(): void
    {
        $this->assertSame('EXISTS', MetaCompare::EXISTS->value);
        $this->assertSame('NOT EXISTS', MetaCompare::NOT_EXISTS->value);
    }

    public function testCasesCount(): void
    {
        $this->assertCount(14, MetaCompare::cases());
    }

    public function testFromString(): void
    {
        $this->assertSame(MetaCompare::EQUAL, MetaCompare::from('='));
        $this->assertSame(MetaCompare::GREATER_THAN, MetaCompare::from('>'));
        $this->assertSame(MetaCompare::EXISTS, MetaCompare::from('EXISTS'));
    }

    public function testTryFromInvalidReturnsNull(): void
    {
        $this->assertNull(MetaCompare::tryFrom('INVALID'));
        $this->assertNull(MetaCompare::tryFrom(''));
    }
}
