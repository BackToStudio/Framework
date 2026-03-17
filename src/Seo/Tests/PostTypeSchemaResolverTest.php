<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\Schema\Generator\PostTypeSchemaResolver;
use BackTo\Framework\Seo\Schema\SchemaType;
use PHPUnit\Framework\TestCase;

class PostTypeSchemaResolverTest extends TestCase
{
    public function testEmptyByDefault(): void
    {
        $resolver = new PostTypeSchemaResolver();
        $this->assertFalse($resolver->supports('post'));
        $this->assertSame([], $resolver->getGenerators());
    }

    public function testAddGenerator(): void
    {
        $generator = $this->createGenerator(new SchemaType('Article'));

        $resolver = new PostTypeSchemaResolver();
        $result = $resolver->addGenerator('post', $generator);

        $this->assertSame($resolver, $result);
        $this->assertTrue($resolver->supports('post'));
        $this->assertFalse($resolver->supports('page'));
    }

    public function testConstructorWithGenerators(): void
    {
        $resolver = new PostTypeSchemaResolver([
            'post' => $this->createGenerator(new SchemaType('Article')),
            'formation' => $this->createGenerator(new SchemaType('Course')),
        ]);

        $this->assertTrue($resolver->supports('post'));
        $this->assertTrue($resolver->supports('formation'));
        $this->assertFalse($resolver->supports('page'));
    }

    public function testResolveCallsGenerator(): void
    {
        $schema = new SchemaType('Article');
        $generator = $this->createGenerator($schema);

        $resolver = new PostTypeSchemaResolver(['post' => $generator]);

        $result = $resolver->resolve('post', 42);

        $this->assertSame($schema, $result);
    }

    public function testResolveReturnsNullForUnsupportedType(): void
    {
        $resolver = new PostTypeSchemaResolver();
        $this->assertNull($resolver->resolve('unknown', 1));
    }

    public function testResolveReturnsNullFromGenerator(): void
    {
        $generator = $this->createGenerator(null);

        $resolver = new PostTypeSchemaResolver(['post' => $generator]);

        $this->assertNull($resolver->resolve('post', 999));
    }

    public function testGetGenerators(): void
    {
        $genA = $this->createGenerator(new SchemaType('Article'));
        $genB = $this->createGenerator(new SchemaType('Course'));

        $resolver = new PostTypeSchemaResolver([
            'post' => $genA,
            'formation' => $genB,
        ]);

        $generators = $resolver->getGenerators();

        $this->assertCount(2, $generators);
        $this->assertSame($genA, $generators['post']);
        $this->assertSame($genB, $generators['formation']);
    }

    private function createGenerator(?SchemaType $returnValue): object
    {
        return new class ($returnValue) {
            public function __construct(private ?SchemaType $returnValue)
            {
            }

            public function generate(?int $postId = null): ?SchemaType
            {
                return $this->returnValue;
            }
        };
    }
}
