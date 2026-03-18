<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\Compose\ValueObject\Slug;
use BackTo\Framework\PostType\Entity\Post;
use BackTo\Framework\PostType\Entity\PostStatus;
use BackTo\Framework\PostType\Entity\PostType;
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
        $this->assertSame(PostStatus::Draft, $post->getStatus());
        $post->setStatus(PostStatus::Publish);
        $this->assertSame(PostStatus::Publish, $post->getStatus());
    }

    public function testStatusFromString()
    {
        $post = new Post();
        $post->setStatus('publish');
        $this->assertSame(PostStatus::Publish, $post->getStatus());
    }

    public function testSlug()
    {
        $post = new Post();
        $this->assertInstanceOf(Slug::class, $post->getSlug());
        $this->assertTrue($post->getSlug()->isEmpty());
        $post->setSlug('abc-def');
        $this->assertSame('abc-def', (string) $post->getSlug());
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
