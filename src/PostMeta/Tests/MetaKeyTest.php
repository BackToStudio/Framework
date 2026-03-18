<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Tests;

use BackTo\Framework\PostMeta\ValueObject\MetaKey;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MetaKeyTest extends TestCase
{
    public function testCreatesFromString(): void
    {
        $key = new MetaKey('_thumbnail_id');
        $this->assertSame('_thumbnail_id', $key->value);
    }

    public function testFromStringFactory(): void
    {
        $key = MetaKey::fromString('my_key');
        $this->assertSame('my_key', $key->value);
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MetaKey('');
    }

    public function testIsProtected(): void
    {
        $this->assertTrue((new MetaKey('_private'))->isProtected());
        $this->assertFalse((new MetaKey('public'))->isProtected());
    }

    public function testEquals(): void
    {
        $a = new MetaKey('key');
        $b = new MetaKey('key');
        $c = new MetaKey('other');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testStringable(): void
    {
        $key = new MetaKey('my_meta_key');
        $this->assertSame('my_meta_key', (string) $key);
    }
}
