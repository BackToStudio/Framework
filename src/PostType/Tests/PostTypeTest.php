<?php

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\PostType\Entity\PostType;
use PHPUnit\Framework\TestCase;

class PostTypeTest extends TestCase
{

    public function testKey()
    {
        $postType = new PostType();
        $this->assertNull($postType->getKey());
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
}
