<?php

namespace BackTo\Framework\PostMeta\Tests;

use BackTo\Framework\PostMeta\PostMetaFactory;
use BackTo\Framework\PostMeta\RegisterPostMeta;
use PHPUnit\Framework\TestCase;

class PostMetaFactoryTest extends TestCase
{
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new PostMetaFactory();
    }

    public function testCanCreateTextPostMeta()
    {
        $register = $this->factory->text('test_text', 'Test Text', 'post');
        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }

    public function testCanCreateTextareaPostMeta()
    {
        $register = $this->factory->textarea('test_textarea', 'Test Textarea', 'post');
        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }

    public function testCanCreateCheckboxPostMeta()
    {
        $register = $this->factory->checkbox('test_checkbox', 'Test Checkbox', 'post');
        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }

    public function testCanCreateNumberPostMeta()
    {
        $register = $this->factory->number('test_number', 'Test Number', 'post');
        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }

    public function testCanCreateEmailPostMeta()
    {
        $register = $this->factory->email('test_email', 'Test Email', 'post');
        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }

    public function testCanCreateUrlPostMeta()
    {
        $register = $this->factory->url('test_url', 'Test URL', 'post');
        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }

    public function testCanCreateDatePostMeta()
    {
        $register = $this->factory->date('test_date', 'Test Date', 'post');
        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }

    public function testCanCreateDatetimePostMeta()
    {
        $register = $this->factory->datetime('test_datetime', 'Test Datetime', 'post');
        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }

    public function testCanGetRegisterInstance()
    {
        $register = $this->factory->getRegister();
        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }

    public function testCanChainMultiplePostMetaCreations()
    {
        $register = $this->factory
            ->text('test_text', 'Test Text', 'post')
            ->textarea('test_textarea', 'Test Textarea', 'post')
            ->checkbox('test_checkbox', 'Test Checkbox', 'post');

        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }

    public function testCanCreatePostMetaWithCustomArgs()
    {
        $args = [
            'description' => 'Test description',
            'required' => true,
            'default' => 'default value'
        ];

        $register = $this->factory->text('test_text', 'Test Text', 'post', $args);
        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }

    public function testCanCreatePostMetaWithMultiplePostTypes()
    {
        $postTypes = ['post', 'page', 'custom_post_type'];
        $register = $this->factory->text('test_text', 'Test Text', $postTypes);
        $this->assertInstanceOf(RegisterPostMeta::class, $register);
    }
} 