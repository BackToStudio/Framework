<?php

namespace BackTo\Framework\Taxonomy\Tests;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;
use BackTo\Framework\Taxonomy\TaxonomyRegistry;
use PHPUnit\Framework\TestCase;

class TaxonomyA implements TaxonomyInterface
{

    public function getKey(): string
    {
        return 'a';
    }

    public function getArgs(): array
    {
        return [];
    }

    public function getPostTypes(): array
    {
        return [];
    }
}

class TaxonomyB implements TaxonomyInterface
{

    public function getKey(): string
    {
        return 'b';
    }

    public function getArgs(): array
    {
        return [];
    }

    public function getPostTypes(): array
    {
        return [ 'abcde' ];
    }
}

class TaxonomyRegistryTest extends TestCase
{

    public function testTaxonomyRegistryShouldImplementsRegistryInterface()
    {
        $registry = new TaxonomyRegistry();
        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyRegistry()
    {
        $registry = new TaxonomyRegistry();
        $this->assertCount(0, $registry->getTaxonomies());
    }

    public function testAddingTaxonomy()
    {
        $registry = new TaxonomyRegistry();
        $taxonomyA = new TaxonomyA();
        $taxonomyB = new TaxonomyB();
        $registry->add($taxonomyA);
        $registry->add($taxonomyB);
        $this->assertCount(2, $registry->getTaxonomies());
        $this->assertContains($taxonomyA, $registry->getTaxonomies());
        $this->assertContains($taxonomyB, $registry->getTaxonomies());
    }
}
