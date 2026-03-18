<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose\Tests;

use BackTo\Framework\Compose\HasId;
use BackTo\Framework\Compose\HasParentId;
use BackTo\Framework\Compose\HasSlug;
use BackTo\Framework\Compose\TextDomain;
use BackTo\Framework\Compose\ValueObject\Slug;
use BackTo\Framework\Contracts\IdInterface;
use BackTo\Framework\Contracts\ParentIdInterface;
use BackTo\Framework\Contracts\SlugInterface;
use PHPUnit\Framework\TestCase;

class HasIdConsumer implements IdInterface
{
    use HasId;
}

class HasSlugConsumer implements SlugInterface
{
    use HasSlug;
}

class HasParentIdConsumer implements ParentIdInterface
{
    use HasParentId;
}

class TextDomainConsumer
{
    use TextDomain;
}

class TraitsTest extends TestCase
{
    // --- HasId ---

    public function testIdDefaultsToNull(): void
    {
        $entity = new HasIdConsumer();

        $this->assertNull($entity->getId());
    }

    public function testSetAndGetId(): void
    {
        $entity = new HasIdConsumer();
        $entity->setId(42);

        $this->assertSame(42, $entity->getId());
    }

    public function testSetIdReturnsSelf(): void
    {
        $entity = new HasIdConsumer();
        $result = $entity->setId(1);

        $this->assertSame($entity, $result);
    }

    public function testSetIdFluentChaining(): void
    {
        $entity = new HasIdConsumer();
        $entity->setId(1)->setId(2)->setId(3);

        $this->assertSame(3, $entity->getId());
    }

    public function testSetIdWithZero(): void
    {
        $entity = new HasIdConsumer();
        $entity->setId(0);

        $this->assertSame(0, $entity->getId());
    }

    public function testSetIdWithNegative(): void
    {
        $entity = new HasIdConsumer();
        $entity->setId(-1);

        $this->assertSame(-1, $entity->getId());
    }

    public function testSetIdWithMaxInt(): void
    {
        $entity = new HasIdConsumer();
        $entity->setId(PHP_INT_MAX);

        $this->assertSame(PHP_INT_MAX, $entity->getId());
    }

    public function testSetIdOverwritesPreviousValue(): void
    {
        $entity = new HasIdConsumer();
        $entity->setId(1);
        $entity->setId(2);

        $this->assertSame(2, $entity->getId());
    }

    // --- HasSlug ---

    public function testSlugDefaultsToEmptySlug(): void
    {
        $entity = new HasSlugConsumer();

        $this->assertInstanceOf(Slug::class, $entity->getSlug());
        $this->assertTrue($entity->getSlug()->isEmpty());
    }

    public function testSetAndGetSlug(): void
    {
        $entity = new HasSlugConsumer();
        $entity->setSlug('my-post-slug');

        $this->assertSame('my-post-slug', (string) $entity->getSlug());
    }

    public function testSetSlugWithValueObject(): void
    {
        $entity = new HasSlugConsumer();
        $slug = new Slug('my-slug');
        $entity->setSlug($slug);

        $this->assertTrue($entity->getSlug()->equals($slug));
    }

    public function testSetSlugReturnsSelf(): void
    {
        $entity = new HasSlugConsumer();
        $result = $entity->setSlug('test');

        $this->assertSame($entity, $result);
    }

    public function testSlugWithSpecialCharacters(): void
    {
        $entity = new HasSlugConsumer();
        $entity->setSlug('a-slug-with-dashes-and-123');

        $this->assertSame('a-slug-with-dashes-and-123', (string) $entity->getSlug());
    }

    public function testSlugWithUnicodeCharacters(): void
    {
        $entity = new HasSlugConsumer();
        $entity->setSlug('mon-article-français');

        $this->assertSame('mon-article-français', (string) $entity->getSlug());
    }

    public function testSlugCanBeSetToEmptyString(): void
    {
        $entity = new HasSlugConsumer();
        $entity->setSlug('something');
        $entity->setSlug('');

        $this->assertTrue($entity->getSlug()->isEmpty());
    }

    // --- HasParentId ---

    public function testParentIdDefaultsToNull(): void
    {
        $entity = new HasParentIdConsumer();

        $this->assertNull($entity->getParentId());
    }

    public function testSetAndGetParentId(): void
    {
        $entity = new HasParentIdConsumer();
        $entity->setParentId(10);

        $this->assertSame(10, $entity->getParentId());
    }

    public function testSetParentIdReturnsSelf(): void
    {
        $entity = new HasParentIdConsumer();
        $result = $entity->setParentId(5);

        $this->assertSame($entity, $result);
    }

    public function testParentIdWithZero(): void
    {
        $entity = new HasParentIdConsumer();
        $entity->setParentId(0);

        $this->assertSame(0, $entity->getParentId());
    }

    public function testParentIdOverwritesPreviousValue(): void
    {
        $entity = new HasParentIdConsumer();
        $entity->setParentId(1);
        $entity->setParentId(99);

        $this->assertSame(99, $entity->getParentId());
    }

    // --- TextDomain ---

    public function testTextDomainDefaultsToEmptyString(): void
    {
        $consumer = new TextDomainConsumer();

        $this->assertSame('', $consumer->getTextDomain());
    }

    public function testSetAndGetTextDomain(): void
    {
        $consumer = new TextDomainConsumer();
        $consumer->setTextDomain('my-plugin');

        $this->assertSame('my-plugin', $consumer->getTextDomain());
    }

    public function testSetTextDomainReturnsSelf(): void
    {
        $consumer = new TextDomainConsumer();
        $result = $consumer->setTextDomain('domain');

        $this->assertSame($consumer, $result);
    }

    public function testTextDomainFluentChaining(): void
    {
        $consumer = new TextDomainConsumer();
        $consumer->setTextDomain('first')->setTextDomain('second');

        $this->assertSame('second', $consumer->getTextDomain());
    }

    public function testTextDomainCanBeResetToEmpty(): void
    {
        $consumer = new TextDomainConsumer();
        $consumer->setTextDomain('something');
        $consumer->setTextDomain('');

        $this->assertSame('', $consumer->getTextDomain());
    }

    public function testTextDomainWithHyphensAndUnderscores(): void
    {
        $consumer = new TextDomainConsumer();
        $consumer->setTextDomain('my_plugin-v2');

        $this->assertSame('my_plugin-v2', $consumer->getTextDomain());
    }
}
