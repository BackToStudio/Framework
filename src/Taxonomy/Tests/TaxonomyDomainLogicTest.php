<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Tests;

use BackTo\Framework\Taxonomy\Entity\Taxonomy;
use PHPUnit\Framework\TestCase;

class TaxonomyDomainLogicTest extends TestCase
{
    // ── isHierarchical ──────────────────────────────────────

    public function testIsHierarchicalTrue(): void
    {
        $tax = new Taxonomy();
        $tax->setArgs(['hierarchical' => true]);
        $this->assertTrue($tax->isHierarchical());
    }

    public function testIsHierarchicalDefault(): void
    {
        $tax = new Taxonomy();
        $this->assertFalse($tax->isHierarchical());
    }

    // ── isExposedInRest ─────────────────────────────────────

    public function testIsExposedInRestTrue(): void
    {
        $tax = new Taxonomy();
        $tax->setArgs(['show_in_rest' => true]);
        $this->assertTrue($tax->isExposedInRest());
    }

    // ── isPubliclyQueryable ─────────────────────────────────

    public function testIsPubliclyQueryableTrue(): void
    {
        $tax = new Taxonomy();
        $tax->setArgs(['publicly_queryable' => true]);
        $this->assertTrue($tax->isPubliclyQueryable());
    }

    public function testIsPubliclyQueryableDefault(): void
    {
        $tax = new Taxonomy();
        $this->assertFalse($tax->isPubliclyQueryable());
    }

    // ── hasKey ──────────────────────────────────────────────

    public function testHasKeyTrue(): void
    {
        $tax = new Taxonomy();
        $tax->setKey('genre');
        $this->assertTrue($tax->hasKey());
    }

    public function testHasKeyFalse(): void
    {
        $tax = new Taxonomy();
        $this->assertFalse($tax->hasKey());
    }

    // ── isAttachedTo ────────────────────────────────────────

    public function testIsAttachedToPostType(): void
    {
        $tax = new Taxonomy();
        $tax->addPostType('post');
        $tax->addPostType('page');
        $this->assertTrue($tax->isAttachedTo('post'));
        $this->assertTrue($tax->isAttachedTo('page'));
        $this->assertFalse($tax->isAttachedTo('product'));
    }

    // ── removePostType ──────────────────────────────────────

    public function testRemovePostType(): void
    {
        $tax = new Taxonomy();
        $tax->addPostType('post');
        $tax->addPostType('page');
        $tax->removePostType('post');
        $this->assertFalse($tax->isAttachedTo('post'));
        $this->assertTrue($tax->isAttachedTo('page'));
    }

    public function testRemoveNonExistentPostTypeIsNoOp(): void
    {
        $tax = new Taxonomy();
        $tax->addPostType('post');
        $tax->removePostType('page'); // doesn't exist
        $this->assertTrue($tax->isAttachedTo('post'));
    }

    // ── key validation ──────────────────────────────────────

    public function testSetKeyThrowsOnTooLong(): void
    {
        $tax = new Taxonomy();
        $this->expectException(\InvalidArgumentException::class);
        $tax->setKey(str_repeat('a', 33));
    }

    public function testSetKeyAccepts32Characters(): void
    {
        $tax = new Taxonomy();
        $tax->setKey(str_repeat('a', 32));
        $this->assertSame(str_repeat('a', 32), $tax->getKey());
    }

    // ── addPostType deduplication ────────────────────────────

    public function testAddPostTypeDeduplicates(): void
    {
        $tax = new Taxonomy();
        $tax->addPostType('post');
        $tax->addPostType('post');
        $this->assertCount(1, $tax->getPostTypes());
    }
}
