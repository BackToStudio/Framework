<?php

namespace BackTo\Framework\PostMeta\Tests;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;
use BackTo\Framework\PostMeta\Entity\PostMetaStructure;
use BackTo\Framework\PostMeta\PostMetaStructureRegistry;
use PHPUnit\Framework\TestCase;

class PostMetaStructureRegistryTest extends TestCase
{
    public function testImplementsRegistryInterface(): void
    {
        $registry = new PostMetaStructureRegistry();
        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyRegistry(): void
    {
        $registry = new PostMetaStructureRegistry();
        $this->assertCount(0, $registry->getPostMetaStructures());
    }

    public function testAddStructure(): void
    {
        $registry = new PostMetaStructureRegistry();
        $structure = new PostMetaStructure();
        $structure->setMetaKey('test_meta');

        $registry->add($structure);

        $this->assertCount(1, $registry->getPostMetaStructures());
    }

    public function testAddReturnsSelf(): void
    {
        $registry = new PostMetaStructureRegistry();
        $result = $registry->add(new PostMetaStructure());
        $this->assertSame($registry, $result);
    }

    public function testAddMultipleStructures(): void
    {
        $registry = new PostMetaStructureRegistry();

        $structureA = new PostMetaStructure();
        $structureA->setMetaKey('meta_a');

        $structureB = new PostMetaStructure();
        $structureB->setMetaKey('meta_b');

        $registry->add($structureA);
        $registry->add($structureB);

        $this->assertCount(2, $registry->getPostMetaStructures());
        $this->assertContains($structureA, $registry->getPostMetaStructures());
        $this->assertContains($structureB, $registry->getPostMetaStructures());
    }
}
