<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Tests;

use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;
use BackTo\Framework\PostMeta\Entity\PostMeta;
use BackTo\Framework\PostMeta\ValueObject\MetaKey;
use BackTo\Framework\PostType\Contracts\PostInterface;
use PHPUnit\Framework\TestCase;

class PostMetaTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $postMeta = new PostMeta();
        $this->assertInstanceOf(PostMetaInterface::class, $postMeta);
    }

    public function testSetAndGetId(): void
    {
        $postMeta = new PostMeta();
        $result = $postMeta->setId(42);
        $this->assertSame(42, $postMeta->getId());
        $this->assertInstanceOf(PostMetaInterface::class, $result);
    }

    public function testSetAndGetMetaKey(): void
    {
        $postMeta = new PostMeta();
        $postMeta->setMetaKey('_thumbnail_id');
        $this->assertInstanceOf(MetaKey::class, $postMeta->getMetaKey());
        $this->assertSame('_thumbnail_id', (string) $postMeta->getMetaKey());
    }

    public function testSetMetaKeyWithValueObject(): void
    {
        $postMeta = new PostMeta();
        $key = new MetaKey('_thumbnail_id');
        $postMeta->setMetaKey($key);
        $this->assertTrue($postMeta->getMetaKey()->equals($key));
    }

    public function testMetaKeyIsProtected(): void
    {
        $postMeta = new PostMeta();
        $postMeta->setMetaKey('_private_key');
        $this->assertTrue($postMeta->getMetaKey()->isProtected());

        $postMeta->setMetaKey('public_key');
        $this->assertFalse($postMeta->getMetaKey()->isProtected());
    }

    public function testSetAndGetMetaValue(): void
    {
        $postMeta = new PostMeta();
        $postMeta->setMetaValue('some_value');
        $this->assertSame('some_value', $postMeta->getMetaValue());
    }

    public function testSetAndGetPostId(): void
    {
        $postMeta = new PostMeta();
        $postMeta->setPostId(10);
        $this->assertSame(10, $postMeta->getPostId());
    }

    public function testSetPostSetsPostId(): void
    {
        $post = $this->createMock(PostInterface::class);
        $post->method('getId')->willReturn(99);

        $postMeta = new PostMeta();
        $postMeta->setPost($post);

        $this->assertSame($post, $postMeta->getPost());
        $this->assertSame(99, $postMeta->getPostId());
    }
}
