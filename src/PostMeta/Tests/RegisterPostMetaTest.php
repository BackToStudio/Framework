<?php

namespace BackTo\Framework\PostMeta\Tests;

use BackTo\Framework\PostMeta\RegisterPostMeta;
use PHPUnit\Framework\TestCase;

class RegisterPostMetaTest extends TestCase
{
    private $register;

    protected function setUp(): void
    {
        parent::setUp();
        $this->register = new RegisterPostMeta();

        // Mock WordPress functions if they don't exist
        if (!function_exists('register_post_meta')) {
            function register_post_meta($post_type, $meta_key, $args) {
                return true;
            }
        }
        if (!function_exists('add_action')) {
            function add_action($hook, $callback) {
                return true;
            }
        }
        if (!function_exists('add_meta_box')) {
            function add_meta_box($id, $title, $callback, $screen, $context, $priority, $callback_args) {
                return true;
            }
        }
        if (!function_exists('wp_nonce_field')) {
            function wp_nonce_field($action, $name) {
                return true;
            }
        }
    }

    public function testCanAddPostMeta()
    {
        $result = $this->register->add('test_key', 'Test Label', 'text', 'post');
        $this->assertInstanceOf(RegisterPostMeta::class, $result);
    }

    public function testCanAddPostMetaWithMultiplePostTypes()
    {
        $result = $this->register->add('test_key', 'Test Label', 'text', ['post', 'page']);
        $this->assertInstanceOf(RegisterPostMeta::class, $result);
    }

    public function testCanAddPostMetaWithCustomArgs()
    {
        $args = [
            'description' => 'Test description',
            'required' => true,
            'default' => 'default value'
        ];

        $result = $this->register->add('test_key', 'Test Label', 'text', 'post', $args);
        $this->assertInstanceOf(RegisterPostMeta::class, $result);
    }

    public function testCanRegisterPostMetas()
    {
        $this->register->add('test_key_1', 'Test Label 1', 'text', 'post');
        $this->register->add('test_key_2', 'Test Label 2', 'textarea', 'page');

        // This should not throw any errors
        $this->register->register();
        $this->assertTrue(true);
    }

    public function testCanRenderMetaBox()
    {
        $post = new \stdClass();
        $post->ID = 1;

        $postMeta = $this->register->add('test_key', 'Test Label', 'text', 'post')->getRegister();
        
        ob_start();
        $this->register->renderMetaBox($post, [
            'args' => ['post_meta' => $postMeta]
        ]);
        $output = ob_get_clean();

        $this->assertIsString($output);
    }

    public function testCanSavePostMeta()
    {
        $_POST = [
            'test_key_nonce' => wp_create_nonce('test_key_nonce'),
            'test_key' => 'test_value'
        ];

        $this->register->add('test_key', 'Test Label', 'text', 'post');
        $this->register->save(1);

        $this->assertTrue(true); // If we got here without errors, the test passed
    }
}

// Helper function for the test
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'test_nonce';
    }
} 