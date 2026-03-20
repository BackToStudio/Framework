<?php

declare(strict_types=1);

namespace BackTo\Framework\Query\Tests;

use BackTo\Framework\Query\SortDirection;
use PHPUnit\Framework\TestCase;

class SortDirectionTest extends TestCase
{
    public function testAscValue(): void
    {
        $this->assertSame('ASC', SortDirection::ASC->value);
    }

    public function testDescValue(): void
    {
        $this->assertSame('DESC', SortDirection::DESC->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(SortDirection::ASC, SortDirection::from('ASC'));
        $this->assertSame(SortDirection::DESC, SortDirection::from('DESC'));
    }

    public function testTryFromInvalidReturnsNull(): void
    {
        $this->assertNull(SortDirection::tryFrom('asc'));
        $this->assertNull(SortDirection::tryFrom('RANDOM'));
        $this->assertNull(SortDirection::tryFrom(''));
    }

    public function testCasesReturnsAllValues(): void
    {
        $cases = SortDirection::cases();
        $this->assertCount(2, $cases);
    }
}
