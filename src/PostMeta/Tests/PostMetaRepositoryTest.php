<?php

namespace BackTo\Framework\PostMeta\Tests;

use BackTo\Framework\PostMeta\Entity\PostMeta;
use BackTo\Framework\PostMeta\PostMetaRegistry;
use BackTo\Framework\PostMeta\PostMetaRepository;
use PHPUnit\Framework\TestCase;

class PostMetaRepositoryTest extends TestCase
{
    private $repository;
    private $postId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PostMetaRepository();
        PostMetaRegistry::clear();

        // Mock WordPress functions if they don't exist
        if (!function_exists('get_post_meta')) {
            function get_post_meta($post_id, $key = '', $single = false) {
                return "mock_value_for_{$key}";
            }
        }
        if (!function_exists('update_post_meta')) {
            function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '') {
                return true;
            }
        }
        if (!function_exists('delete_post_meta')) {
            function delete_post_meta($post_id, $meta_key, $meta_value = '') {
                return true;
            }
        }
    }

    public function testCanGetPostMetaValue()
    {
        $value = $this->repository->get($this->postId, 'test_key');
        $this->assertEquals('mock_value_for_test_key', $value);
    }

    public function testCanUpdatePostMetaValue()
    {
        $result = $this->repository->update($this->postId, 'test_key', 'new_value');
        $this->assertTrue($result);
    }

    public function testCanDeletePostMetaValue()
    {
        $result = $this->repository->delete($this->postId, 'test_key');
        $this->assertTrue($result);
    }

    public function testCanGetAllPostMetaValues()
    {
        $all = $this->repository->all($this->postId);
        $this->assertIsArray($all);
    }

    public function testCanGetAllRegisteredPostMetaValues()
    {
        PostMetaRegistry::register(new PostMeta('test_meta_1', 'Test Meta 1'));
        PostMetaRegistry::register(new PostMeta('test_meta_2', 'Test Meta 2'));

        $values = $this->repository->allRegistered($this->postId);
        
        $this->assertIsArray($values);
        $this->assertArrayHasKey('test_meta_1', $values);
        $this->assertArrayHasKey('test_meta_2', $values);
        $this->assertEquals('mock_value_for_test_meta_1', $values['test_meta_1']);
        $this->assertEquals('mock_value_for_test_meta_2', $values['test_meta_2']);
    }

    public function testCanGetPostMetaValuesForMultiplePosts()
    {
        $postIds = [1, 2, 3];
        $values = $this->repository->getForPosts($postIds, 'test_key');

        $this->assertCount(3, $values);
        foreach ($postIds as $postId) {
            $this->assertArrayHasKey($postId, $values);
            $this->assertEquals('mock_value_for_test_key', $values[$postId]);
        }
    }
} 