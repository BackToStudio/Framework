<?php

declare(strict_types=1);

namespace BackTo\Framework\Options\Tests;

use BackTo\Framework\Options\HasArrayOptions;
use PHPUnit\Framework\TestCase;

class HasArrayOptionsConsumer
{
    use HasArrayOptions;
}

class HasArrayOptionsTest extends TestCase
{
    public function testSetAndGetString(): void
    {
        $consumer = new HasArrayOptionsConsumer();
        $consumer->setOptions(['name' => 'John']);

        $this->assertSame('John', $consumer->getString('name'));
    }

    public function testGetStringReturnsNullForMissingKey(): void
    {
        $consumer = new HasArrayOptionsConsumer();
        $consumer->setOptions([]);

        $this->assertNull($consumer->getString('missing'));
    }

    public function testGetArray(): void
    {
        $consumer = new HasArrayOptionsConsumer();
        $consumer->setOptions(['items' => ['a', 'b']]);

        $this->assertSame(['a', 'b'], $consumer->getArray('items'));
    }

    public function testIsInOptionsChecksValues(): void
    {
        $consumer = new HasArrayOptionsConsumer();
        $consumer->setOptions(['a' => 1, 'b' => 2]);

        $this->assertFalse($consumer->isInOptions('a'));
        $this->assertFalse($consumer->isInOptions('missing'));
    }

    public function testIsInOptionsWithMatchingValue(): void
    {
        $consumer = new HasArrayOptionsConsumer();
        $consumer->setOptions([0 => 'hello', 1 => 'world']);

        $this->assertTrue($consumer->isInOptions('hello'));
        $this->assertFalse($consumer->isInOptions('missing'));
    }
}
