<?php

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\PostType\Contracts\PostTypeInterface;
use BackTo\Framework\PostType\PostType;
use BackTo\Framework\PostType\PostTypeRegistry;
use PHPUnit\Framework\TestCase;

class PostTypeA implements PostTypeInterface
{

    public function getKey(): string
    {
        return 'a';
    }

    public function getArgs(): array
    {
        return [];
    }
}

class PostTypeB implements PostTypeInterface
{

    public function getKey(): string
    {
        return 'b';
    }

    public function getArgs(): array
    {
        return [];
    }
}

class PostTypeRegistryTest extends TestCase
{

    public function testPostTypeRegistryShouldImplementsRegistryInterface()
    {
        $registry = new PostTypeRegistry();
        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyRegistry()
    {
        $registry = new PostTypeRegistry();
        $this->assertCount(0, $registry->getPostTypes());
    }

    public function testAddingPostType()
    {
        $registry = new PostTypeRegistry();
        $postTypeA = new PostTypeA();
        $postTypeB = new PostTypeB();
        $registry->add($postTypeA);
        $registry->add($postTypeB);
        $this->assertCount(2, $registry->getPostTypes());
    }
}
