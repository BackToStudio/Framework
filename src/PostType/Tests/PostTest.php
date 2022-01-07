<?php

namespace Fantassin\Core\WordPress\PostType\Tests;

use Fantassin\Core\WordPress\PostType\Entity\Post;
use Fantassin\Core\WordPress\PostType\Entity\PostType;
use PHPUnit\Framework\TestCase;
use DateTime;

class PostTest extends TestCase
{

    public function testId()
    {
        $post = new Post();
        $this->assertNull($post->getId());
        $post->setId(1);
        $this->assertSame(1, $post->getId());
    }

    public function testTitle()
    {
        $post = new Post();
        $this->assertEmpty($post->getTitle());
        $post->setTitle('fghijk');
        $this->assertSame('fghijk', $post->getTitle());
    }

    public function testContent()
    {
        $post = new Post();
        $this->assertEmpty($post->getContent());
        $post->setContent('abcde');
        $this->assertSame('abcde', $post->getContent());
    }

    public function testStatus()
    {
        $post = new Post();
        $this->assertEmpty($post->getStatus());
        $post->setStatus('published');
        $this->assertSame('published', $post->getStatus());
    }

    public function testSlug()
    {
        $post = new Post();
        $this->assertEmpty($post->getSlug());
        $post->setSlug('abc-def');
        $this->assertSame('abc-def', $post->getSlug());
    }

    public function testParentId()
    {
        $post = new Post();
        $this->assertNull($post->getParentId());
        $post->setParentId(1);
        $this->assertSame(1, $post->getParentId());
    }

    public function testPostType()
    {
        $post = new Post();
        $this->assertEmpty($post->getPostType());
        $post->setPostType('fghijk');
        $this->assertSame('fghijk', $post->getPostType());
    }

    public function testAuthor()
    {
        $post = new Post();
        $this->assertEmpty($post->getAuthor());
        $post->setAuthor('fghijk');
        $this->assertSame('fghijk', $post->getAuthor());
    }

    public function testModifiedAt()
    {
        $post = new Post();
        $date = new DateTime();
        $this->assertNull($post->getModifiedAt());
        $post->setModifiedAt($date);
        $this->assertSame($date->format('YYYY-MM-dd'), $post->getModifiedAt()->format('YYYY-MM-dd'));
    }

    public function testPublishedAt()
    {
        $post = new Post();
        $date = new DateTime();
        $this->assertNull($post->getPublishedAt());
        $post->setPublishedAt($date);
        $this->assertSame($date->format('YYYY-MM-dd'), $post->getPublishedAt()->format('YYYY-MM-dd'));
    }
}
