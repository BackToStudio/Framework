<?php

namespace BackTo\Framework\PostMeta\Tests;

use BackTo\Framework\PostMeta\Entity\PostMeta;
use PHPUnit\Framework\TestCase;

class PostMetaTest extends TestCase
{
    public function testCanCreatePostMeta()
    {
        $postMeta = new PostMeta(
            'test_meta',
            'Test Meta',
            'text',
            ['post'],
            ['description' => 'Test description']
        );

        $this->assertEquals('test_meta', $postMeta->getKey());
        $this->assertEquals('Test Meta', $postMeta->getLabel());
        $this->assertEquals('text', $postMeta->getType());
        $this->assertEquals(['post'], $postMeta->getPostTypes());
        $this->assertEquals('Test description', $postMeta->getArgs()['description']);
    }

    public function testThrowsExceptionOnEmptyKey()
    {
        $this->expectException(\Exception::class);
        new PostMeta('', 'Test Meta');
    }

    public function testDefaultValues()
    {
        $postMeta = new PostMeta('test_meta', 'Test Meta');

        $this->assertEquals('text', $postMeta->getType());
        $this->assertEquals([], $postMeta->getPostTypes());
        $this->assertTrue($postMeta->getArgs()['single']);
        $this->assertTrue($postMeta->getArgs()['show_in_rest']);
    }
} 