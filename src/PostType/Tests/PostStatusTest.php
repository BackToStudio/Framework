<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\PostType\Entity\PostStatus;
use PHPUnit\Framework\TestCase;

class PostStatusTest extends TestCase
{
    public function testAllCasesHaveStringValues(): void
    {
        $this->assertSame('publish', PostStatus::Publish->value);
        $this->assertSame('draft', PostStatus::Draft->value);
        $this->assertSame('pending', PostStatus::Pending->value);
        $this->assertSame('private', PostStatus::Private->value);
        $this->assertSame('trash', PostStatus::Trash->value);
        $this->assertSame('auto-draft', PostStatus::AutoDraft->value);
        $this->assertSame('inherit', PostStatus::Inherit->value);
        $this->assertSame('future', PostStatus::Future->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(PostStatus::Publish, PostStatus::from('publish'));
        $this->assertSame(PostStatus::Draft, PostStatus::from('draft'));
    }

    public function testTryFromWithInvalidValue(): void
    {
        $this->assertNull(PostStatus::tryFrom('nonexistent'));
    }

    public function testIsPublic(): void
    {
        $this->assertTrue(PostStatus::Publish->isPublic());
        $this->assertFalse(PostStatus::Draft->isPublic());
        $this->assertFalse(PostStatus::Private->isPublic());
    }

    public function testIsEditable(): void
    {
        $this->assertTrue(PostStatus::Draft->isEditable());
        $this->assertTrue(PostStatus::Pending->isEditable());
        $this->assertTrue(PostStatus::AutoDraft->isEditable());
        $this->assertTrue(PostStatus::Future->isEditable());
        $this->assertFalse(PostStatus::Publish->isEditable());
        $this->assertFalse(PostStatus::Trash->isEditable());
    }

    public function testIsViewable(): void
    {
        $this->assertTrue(PostStatus::Publish->isViewable());
        $this->assertTrue(PostStatus::Private->isViewable());
        $this->assertFalse(PostStatus::Draft->isViewable());
        $this->assertFalse(PostStatus::Trash->isViewable());
    }
}
