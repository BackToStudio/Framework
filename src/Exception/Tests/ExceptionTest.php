<?php

declare(strict_types=1);

namespace BackTo\Framework\Exception\Tests;

use BackTo\Framework\Exception\AssetBuildNotFoundException;
use BackTo\Framework\Exception\ContainerBuildException;
use BackTo\Framework\Exception\FrameworkException;
use BackTo\Framework\Exception\InvalidPostTypeException;
use BackTo\Framework\Exception\InvalidTaxonomyException;
use BackTo\Framework\Exception\PostNotFoundException;
use BackTo\Framework\Exception\TermNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExceptionTest extends TestCase
{
    public function testFrameworkExceptionExtendsRuntimeException(): void
    {
        $exception = new FrameworkException('test');
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame('test', $exception->getMessage());
    }

    public function testContainerBuildExceptionExtendsFrameworkException(): void
    {
        $exception = new ContainerBuildException('container error');
        $this->assertInstanceOf(FrameworkException::class, $exception);
        $this->assertSame('container error', $exception->getMessage());
    }

    // ── PostNotFoundException ────────────────────────────────

    public function testPostNotFoundWithId(): void
    {
        $exception = PostNotFoundException::withId(42);
        $this->assertInstanceOf(FrameworkException::class, $exception);
        $this->assertStringContainsString('42', $exception->getMessage());
        $this->assertStringContainsString('not found', $exception->getMessage());
    }

    public function testPostNotFoundWithIdZero(): void
    {
        $exception = PostNotFoundException::withId(0);
        $this->assertStringContainsString('0', $exception->getMessage());
    }

    // ── TermNotFoundException ────────────────────────────────

    public function testTermNotFoundWithId(): void
    {
        $exception = TermNotFoundException::withId(7, 'category');
        $this->assertInstanceOf(FrameworkException::class, $exception);
        $this->assertStringContainsString('7', $exception->getMessage());
        $this->assertStringContainsString('category', $exception->getMessage());
    }

    public function testTermNotFoundWithCustomTaxonomy(): void
    {
        $exception = TermNotFoundException::withId(15, 'product_cat');
        $this->assertStringContainsString('product_cat', $exception->getMessage());
    }

    // ── InvalidPostTypeException ────────────────────────────

    public function testInvalidPostTypeEmptyKey(): void
    {
        $exception = InvalidPostTypeException::emptyKey();
        $this->assertInstanceOf(FrameworkException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
        $this->assertStringContainsString('post type', strtolower($exception->getMessage()));
    }

    // ── InvalidTaxonomyException ────────────────────────────

    public function testInvalidTaxonomyEmptyKey(): void
    {
        $exception = InvalidTaxonomyException::emptyKey();
        $this->assertInstanceOf(FrameworkException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
        $this->assertStringContainsString('taxonomy', strtolower($exception->getMessage()));
    }

    // ── AssetBuildNotFoundException ──────────────────────────

    public function testAssetBuildNotFoundForDirectory(): void
    {
        $exception = AssetBuildNotFoundException::forDirectory('/path/to/assets');
        $this->assertInstanceOf(FrameworkException::class, $exception);
        $this->assertStringContainsString('/path/to/assets', $exception->getMessage());
        $this->assertStringContainsString('npm', strtolower($exception->getMessage()));
    }

    // ── Hierarchy integrity ─────────────────────────────────

    public function testAllExceptionsExtendFrameworkException(): void
    {
        $classes = [
            PostNotFoundException::class,
            TermNotFoundException::class,
            InvalidPostTypeException::class,
            InvalidTaxonomyException::class,
            AssetBuildNotFoundException::class,
            ContainerBuildException::class,
        ];

        foreach ($classes as $class) {
            $this->assertTrue(
                is_subclass_of($class, FrameworkException::class),
                sprintf('%s should extend FrameworkException', $class)
            );
        }
    }

    public function testExceptionsAreCatchableAsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        throw PostNotFoundException::withId(1);
    }

    public function testExceptionsAreCatchableAsFrameworkException(): void
    {
        $this->expectException(FrameworkException::class);
        throw TermNotFoundException::withId(1, 'category');
    }

    public function testExceptionCodeDefaultsToZero(): void
    {
        $exception = PostNotFoundException::withId(1);
        $this->assertSame(0, $exception->getCode());
    }
}
