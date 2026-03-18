<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Tests;

use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;
use BackTo\Framework\PostMeta\Entity\PostMetaStructure;
use BackTo\Framework\PostMeta\ValueObject\MetaKey;
use PHPUnit\Framework\TestCase;

class PostMetaStructureTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $structure = new PostMetaStructure();
        $this->assertInstanceOf(PostMetaStructureInterface::class, $structure);
    }

    public function testDefaults(): void
    {
        $structure = new PostMetaStructure();
        $this->assertSame('post', $structure->getObjectType());
        $this->assertSame('', $structure->getType());
        $this->assertSame('', $structure->getLabel());
        $this->assertSame('', $structure->getDescription());
        $this->assertTrue($structure->isSingle());
        $this->assertNull($structure->getDefault());
        $this->assertNull($structure->getSanitizeCallback());
        $this->assertNull($structure->getAuthCallback());
        $this->assertFalse($structure->isShowInRest());
        $this->assertFalse($structure->isRevisionsEnabled());
    }

    public function testGetMetaKeyThrowsWhenNotSet(): void
    {
        $structure = new PostMetaStructure();
        $this->expectException(\LogicException::class);
        $structure->getMetaKey();
    }

    public function testSetMetaKey(): void
    {
        $structure = new PostMetaStructure();
        $result = $structure->setMetaKey('my_meta');
        $this->assertInstanceOf(MetaKey::class, $structure->getMetaKey());
        $this->assertSame('my_meta', (string) $structure->getMetaKey());
        $this->assertInstanceOf(PostMetaStructureInterface::class, $result);
    }

    public function testSetType(): void
    {
        $structure = new PostMetaStructure();
        $structure->setType('string');
        $this->assertSame('string', $structure->getType());
    }

    public function testSetObjectType(): void
    {
        $structure = new PostMetaStructure();
        $structure->setObjectType('comment');
        $this->assertSame('comment', $structure->getObjectType());
    }

    public function testShowInRest(): void
    {
        $structure = new PostMetaStructure();
        $structure->showInRest();
        $this->assertTrue($structure->isShowInRest());
        $structure->dontShowInRest();
        $this->assertFalse($structure->isShowInRest());
    }

    public function testSetDefault(): void
    {
        $structure = new PostMetaStructure();
        $structure->setDefault('hello');
        $this->assertSame('hello', $structure->getDefault());
    }

    public function testFluentInterface(): void
    {
        $structure = new PostMetaStructure();
        $result = $structure
            ->setMetaKey('key')
            ->setType('string')
            ->setLabel('Label')
            ->setDescription('Desc')
            ->setSingle(false)
            ->setDefault('val')
            ->setShowInRest(true)
            ->setRevisionsEnabled(true);

        $this->assertSame('key', (string) $result->getMetaKey());
        $this->assertSame('string', $result->getType());
        $this->assertSame('Label', $result->getLabel());
        $this->assertSame('Desc', $result->getDescription());
        $this->assertFalse($result->isSingle());
        $this->assertSame('val', $result->getDefault());
        $this->assertTrue($result->isShowInRest());
        $this->assertTrue($result->isRevisionsEnabled());
    }
}
