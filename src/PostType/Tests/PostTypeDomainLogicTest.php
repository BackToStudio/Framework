<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\PostType\Entity\PostType;
use PHPUnit\Framework\TestCase;

class PostTypeDomainLogicTest extends TestCase
{
    // ── isHierarchical ──────────────────────────────────────

    public function testIsHierarchicalTrue(): void
    {
        $postType = new PostType();
        $postType->setArgs(['hierarchical' => true]);
        $this->assertTrue($postType->isHierarchical());
    }

    public function testIsHierarchicalFalse(): void
    {
        $postType = new PostType();
        $postType->setArgs(['hierarchical' => false]);
        $this->assertFalse($postType->isHierarchical());
    }

    public function testIsHierarchicalDefaultsFalse(): void
    {
        $postType = new PostType();
        $this->assertFalse($postType->isHierarchical());
    }

    // ── isExposedInRest ─────────────────────────────────────

    public function testIsExposedInRestTrue(): void
    {
        $postType = new PostType();
        $postType->setArgs(['show_in_rest' => true]);
        $this->assertTrue($postType->isExposedInRest());
    }

    public function testIsExposedInRestDefault(): void
    {
        $postType = new PostType();
        $this->assertFalse($postType->isExposedInRest());
    }

    // ── isPublic ────────────────────────────────────────────

    public function testIsPublicTrue(): void
    {
        $postType = new PostType();
        $postType->setArgs(['public' => true]);
        $this->assertTrue($postType->isPublic());
    }

    public function testIsPublicDefault(): void
    {
        $postType = new PostType();
        $this->assertFalse($postType->isPublic());
    }

    // ── supports ────────────────────────────────────────────

    public function testSupportsFeature(): void
    {
        $postType = new PostType();
        $postType->setArgs(['supports' => ['title', 'editor', 'thumbnail']]);
        $this->assertTrue($postType->supports('title'));
        $this->assertTrue($postType->supports('editor'));
        $this->assertTrue($postType->supports('thumbnail'));
    }

    public function testDoesNotSupportFeature(): void
    {
        $postType = new PostType();
        $postType->setArgs(['supports' => ['title']]);
        $this->assertFalse($postType->supports('editor'));
    }

    public function testSupportsWithNoSupportsArg(): void
    {
        $postType = new PostType();
        $this->assertFalse($postType->supports('title'));
    }

    // ── hasKey ──────────────────────────────────────────────

    public function testHasKeyTrue(): void
    {
        $postType = new PostType();
        $postType->setKey('product');
        $this->assertTrue($postType->hasKey());
    }

    public function testHasKeyFalseWhenEmpty(): void
    {
        $postType = new PostType();
        $this->assertFalse($postType->hasKey());
    }

    // ── key validation ──────────────────────────────────────

    public function testSetKeyThrowsOnTooLong(): void
    {
        $postType = new PostType();
        $this->expectException(\InvalidArgumentException::class);
        $postType->setKey(str_repeat('a', 21));
    }

    public function testSetKeyAccepts20Characters(): void
    {
        $postType = new PostType();
        $postType->setKey(str_repeat('a', 20));
        $this->assertSame(str_repeat('a', 20), $postType->getKey());
    }
}
