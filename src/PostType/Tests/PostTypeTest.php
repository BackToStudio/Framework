<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\PostType\Entity\PostType;
use PHPUnit\Framework\TestCase;

class PostTypeTest extends TestCase
{

    public function testKey()
    {
        $postType = new PostType();
        $this->assertSame('', $postType->getKey());
        $postType->setKey('abcde');
        $this->assertSame('abcde', $postType->getKey());
    }

    public function testArgs()
    {
        $postType = new PostType();
        $this->assertCount(0, $postType->getArgs());
        $args = [
            'labels' => []
        ];
        $postType->setArgs($args);
        $this->assertArrayHasKey('labels', $postType->getArgs());
    }

    public function testSetKeyRejectsLongKey(): void
    {
        $postType = new PostType();
        $this->expectException(\InvalidArgumentException::class);
        $postType->setKey(str_repeat('a', 21));
    }

    public function testSetKeyAcceptsMaxLength(): void
    {
        $postType = new PostType();
        $postType->setKey(str_repeat('a', 20));
        $this->assertSame(str_repeat('a', 20), $postType->getKey());
    }
}
