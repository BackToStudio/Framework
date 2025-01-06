<?php

namespace BackTo\Framework\PostMeta\Tests;

use BackTo\Framework\PostMeta\Entity\PostMeta;
use BackTo\Framework\PostMeta\PostMetaRegistry;
use PHPUnit\Framework\TestCase;

class PostMetaRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        PostMetaRegistry::clear();
    }

    public function testCanRegisterPostMeta()
    {
        $postMeta = new PostMeta('test_meta', 'Test Meta');
        PostMetaRegistry::register($postMeta);

        $this->assertTrue(PostMetaRegistry::has('test_meta'));
        $this->assertSame($postMeta, PostMetaRegistry::get('test_meta'));
    }

    public function testCanGetAllRegisteredPostMetas()
    {
        $postMeta1 = new PostMeta('test_meta_1', 'Test Meta 1');
        $postMeta2 = new PostMeta('test_meta_2', 'Test Meta 2');

        PostMetaRegistry::register($postMeta1);
        PostMetaRegistry::register($postMeta2);

        $all = PostMetaRegistry::all();
        $this->assertCount(2, $all);
        $this->assertSame($postMeta1, $all['test_meta_1']);
        $this->assertSame($postMeta2, $all['test_meta_2']);
    }

    public function testCanRemovePostMeta()
    {
        $postMeta = new PostMeta('test_meta', 'Test Meta');
        PostMetaRegistry::register($postMeta);
        
        $this->assertTrue(PostMetaRegistry::has('test_meta'));
        
        PostMetaRegistry::remove('test_meta');
        $this->assertFalse(PostMetaRegistry::has('test_meta'));
        $this->assertNull(PostMetaRegistry::get('test_meta'));
    }

    public function testCanClearAllPostMetas()
    {
        PostMetaRegistry::register(new PostMeta('test_meta_1', 'Test Meta 1'));
        PostMetaRegistry::register(new PostMeta('test_meta_2', 'Test Meta 2'));

        $this->assertCount(2, PostMetaRegistry::all());

        PostMetaRegistry::clear();
        $this->assertCount(0, PostMetaRegistry::all());
    }

    public function testGetReturnsNullForNonExistentKey()
    {
        $this->assertNull(PostMetaRegistry::get('non_existent'));
    }
} 