<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\PostType\Entity\Post;
use BackTo\Framework\PostType\Entity\PostStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PostDomainLogicTest extends TestCase
{
    // ── isPublished ─────────────────────────────────────────

    public function testIsPublishedWhenStatusIsPublish(): void
    {
        $post = new Post();
        $post->setStatus(PostStatus::Publish);
        $this->assertTrue($post->isPublished());
    }

    public function testIsNotPublishedWhenStatusIsDraft(): void
    {
        $post = new Post();
        $post->setStatus(PostStatus::Draft);
        $this->assertFalse($post->isPublished());
    }

    public function testIsNotPublishedWhenStatusIsPrivate(): void
    {
        $post = new Post();
        $post->setStatus(PostStatus::Private);
        $this->assertFalse($post->isPublished());
    }

    // ── isDraft ─────────────────────────────────────────────

    public function testIsDraftWhenStatusIsDraft(): void
    {
        $post = new Post();
        $post->setStatus(PostStatus::Draft);
        $this->assertTrue($post->isDraft());
    }

    public function testIsDraftWhenStatusIsPending(): void
    {
        $post = new Post();
        $post->setStatus(PostStatus::Pending);
        $this->assertTrue($post->isDraft());
    }

    public function testIsDraftWhenStatusIsAutoDraft(): void
    {
        $post = new Post();
        $post->setStatus(PostStatus::AutoDraft);
        $this->assertTrue($post->isDraft());
    }

    public function testIsNotDraftWhenPublished(): void
    {
        $post = new Post();
        $post->setStatus(PostStatus::Publish);
        $this->assertFalse($post->isDraft());
    }

    // ── isTrashed ───────────────────────────────────────────

    public function testIsTrashedWhenStatusIsTrash(): void
    {
        $post = new Post();
        $post->setStatus(PostStatus::Trash);
        $this->assertTrue($post->isTrashed());
    }

    public function testIsNotTrashedWhenPublished(): void
    {
        $post = new Post();
        $post->setStatus(PostStatus::Publish);
        $this->assertFalse($post->isTrashed());
    }

    // ── hasBeenModifiedAfterPublication ──────────────────────

    public function testHasBeenModifiedAfterPublication(): void
    {
        $post = new Post();
        $post->setPublishedAt(new DateTimeImmutable('2024-01-01'));
        $post->setModifiedAt(new DateTimeImmutable('2024-06-15'));
        $this->assertTrue($post->hasBeenModifiedAfterPublication());
    }

    public function testHasNotBeenModifiedAfterPublication(): void
    {
        $post = new Post();
        $date = new DateTimeImmutable('2024-01-01');
        $post->setPublishedAt($date);
        $post->setModifiedAt($date);
        $this->assertFalse($post->hasBeenModifiedAfterPublication());
    }

    public function testHasNotBeenModifiedWhenNoDates(): void
    {
        $post = new Post();
        $this->assertFalse($post->hasBeenModifiedAfterPublication());
    }

    public function testHasNotBeenModifiedWhenNoModifiedDate(): void
    {
        $post = new Post();
        $post->setPublishedAt(new DateTimeImmutable('2024-01-01'));
        $this->assertFalse($post->hasBeenModifiedAfterPublication());
    }

    // ── hasContent ──────────────────────────────────────────

    public function testHasContentWhenNotEmpty(): void
    {
        $post = new Post();
        $post->setContent('Hello World');
        $this->assertTrue($post->hasContent());
    }

    public function testHasNoContentWhenEmpty(): void
    {
        $post = new Post();
        $this->assertFalse($post->hasContent());
    }

    public function testHasNoContentWhenWhitespace(): void
    {
        $post = new Post();
        $post->setContent('   ');
        $this->assertFalse($post->hasContent());
    }

    // ── hasExcerpt ──────────────────────────────────────────

    public function testHasExcerptWhenNotEmpty(): void
    {
        $post = new Post();
        $post->setExcerpt('A summary');
        $this->assertTrue($post->hasExcerpt());
    }

    public function testHasNoExcerptWhenEmpty(): void
    {
        $post = new Post();
        $this->assertFalse($post->hasExcerpt());
    }

    public function testHasNoExcerptWhenWhitespace(): void
    {
        $post = new Post();
        $post->setExcerpt('  ');
        $this->assertFalse($post->hasExcerpt());
    }
}
