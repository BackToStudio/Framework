<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose\Tests;

use BackTo\Framework\Contracts\Slug;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SlugTest extends TestCase
{
    public function testCreatesFromString(): void
    {
        $slug = new Slug('my-post');
        $this->assertSame('my-post', $slug->value);
    }

    public function testFromStringFactory(): void
    {
        $slug = Slug::fromString('my-post');
        $this->assertSame('my-post', $slug->value);
    }

    public function testRejectsSpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Slug('my post');
    }

    public function testAllowsEmptyString(): void
    {
        $slug = new Slug('');
        $this->assertTrue($slug->isEmpty());
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue((new Slug(''))->isEmpty());
        $this->assertFalse((new Slug('abc'))->isEmpty());
    }

    public function testEquals(): void
    {
        $a = new Slug('test');
        $b = new Slug('test');
        $c = new Slug('other');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testStringable(): void
    {
        $slug = new Slug('hello-world');
        $this->assertSame('hello-world', (string) $slug);
    }

    public function testAllowsUnicodeCharacters(): void
    {
        $slug = new Slug('mon-article-français');
        $this->assertSame('mon-article-français', $slug->value);
    }

    public function testAllowsNumbers(): void
    {
        $slug = new Slug('post-123');
        $this->assertSame('post-123', $slug->value);
    }
}
