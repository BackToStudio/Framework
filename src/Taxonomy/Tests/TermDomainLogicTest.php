<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Tests;

use BackTo\Framework\Taxonomy\Entity\Term;
use PHPUnit\Framework\TestCase;

class TermDomainLogicTest extends TestCase
{
    // ── isTopLevel ──────────────────────────────────────────

    public function testIsTopLevelWhenNoParent(): void
    {
        $term = new Term();
        $this->assertTrue($term->isTopLevel());
    }

    public function testIsTopLevelWhenParentIdIsZero(): void
    {
        $term = new Term();
        $term->setParentId(0);
        $this->assertTrue($term->isTopLevel());
    }

    public function testIsNotTopLevelWhenParentIdIsSet(): void
    {
        $term = new Term();
        $term->setParentId(5);
        $this->assertFalse($term->isTopLevel());
    }

    // ── belongsTo ───────────────────────────────────────────

    public function testBelongsToTaxonomy(): void
    {
        $term = new Term();
        $term->setTaxonomy('category');
        $this->assertTrue($term->belongsTo('category'));
        $this->assertFalse($term->belongsTo('post_tag'));
    }

    // ── hasDescription ──────────────────────────────────────

    public function testHasDescriptionTrue(): void
    {
        $term = new Term();
        $term->setDescription('A category for tech posts');
        $this->assertTrue($term->hasDescription());
    }

    public function testHasDescriptionFalseWhenEmpty(): void
    {
        $term = new Term();
        $this->assertFalse($term->hasDescription());
    }

    public function testHasDescriptionFalseWhenWhitespace(): void
    {
        $term = new Term();
        $term->setDescription('   ');
        $this->assertFalse($term->hasDescription());
    }
}
