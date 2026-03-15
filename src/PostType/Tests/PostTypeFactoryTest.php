<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\Exception\InvalidPostTypeException;
use BackTo\Framework\PostType\Entity\PostType;
use BackTo\Framework\PostType\PostTypeFactory;
use PHPUnit\Framework\TestCase;

class PostTypeFactoryTest extends TestCase
{
    private function givenThereIsEmptyKey(): string
    {
        return '';
    }

    private function givenThereIsKey(): string
    {
        return 'abcde';
    }

    private function givenThereAreEmptyArgs(): array
    {
        return [];
    }

    private function givenThereAreSpecificArgs(): array
    {
        return [
            'show_ui' => false,
            'show_in_rest' => false,
            'publicly_queryable' => false,
        ];
    }

    private function givenThereIsHierarchicalPostTypeArgs(): array
    {
        return [
            'hierarchical' => true,
        ];
    }

    private function givenThereAreEditorSupports(): array
    {
        return [
            'supports' => ['editor'],
        ];
    }

    public function whenImCreatingPostType(string $key, array $args): PostType
    {
        $factory = new PostTypeFactory();
        return $factory->createPostType($key, $args);
    }

    public function thenIShouldHaveDefaultArgs(array $args): void
    {
        $this->assertArrayHasKey('show_ui', $args);
        $this->assertArrayHasKey('show_in_rest', $args);
        $this->assertArrayHasKey('publicly_queryable', $args);
        $this->assertTrue($args['show_ui']);
        $this->assertTrue($args['show_in_rest']);
        $this->assertTrue($args['publicly_queryable']);
    }

    public function thenIShouldHaveHierarchicalPostTypeSupportsExist(array $args): void
    {
        $this->assertArrayHasKey('hierarchical', $args);
        $this->assertTrue($args['hierarchical']);
    }

    private function thenIShouldHaveSameArgs(array $args, array $givenArgs): void
    {
        $this->assertSame($args['show_ui'], $givenArgs['show_ui']);
        $this->assertSame($args['show_in_rest'], $givenArgs['show_in_rest']);
        $this->assertSame($args['publicly_queryable'], $givenArgs['publicly_queryable']);
    }

    private function thenIShouldHaveCustomFieldSupports(array $args): void
    {
        $this->assertArrayHasKey('supports', $args);
        $this->assertContains('custom-fields', $args['supports']);
    }

    private function thenIShouldHaveRevisionsSupports(array $args): void
    {
        $this->assertArrayHasKey('supports', $args);
        $this->assertContains('revisions', $args['supports']);
    }

    private function thenIShouldHaveTitleSupports(array $args): void
    {
        $this->assertArrayHasKey('supports', $args);
        $this->assertContains('title', $args['supports']);
    }

    public function testNotAllowEmptyPostTypeKey(): void
    {
        $this->expectException(InvalidPostTypeException::class);
        $key = $this->givenThereIsEmptyKey();
        $args = $this->givenThereAreEmptyArgs();
        $this->whenImCreatingPostType($key, $args);
    }

    public function testDefaultPostTypeArgs(): void
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereAreEmptyArgs();
        $postType = $this->whenImCreatingPostType($key, $args);
        $this->thenIShouldHaveDefaultArgs($postType->getArgs());
    }

    public function testHierarchicalPostType(): void
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereIsHierarchicalPostTypeArgs();
        $postType = $this->whenImCreatingPostType($key, $args);
        $this->thenIShouldHaveHierarchicalPostTypeSupportsExist($postType->getArgs());
    }

    public function testGivenPostTypeArgs(): void
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereAreSpecificArgs();
        $postType = $this->whenImCreatingPostType($key, $args);
        $this->thenIShouldHaveSameArgs($postType->getArgs(), $args);
    }

    public function testPostTypeEditorSupports(): void
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereAreEditorSupports();
        $postType = $this->whenImCreatingPostType($key, $args);
        $this->thenIShouldHaveCustomFieldSupports($postType->getArgs());
        $this->thenIShouldHaveRevisionsSupports($postType->getArgs());
        $this->thenIShouldHaveTitleSupports($postType->getArgs());
    }

    public function testDefaultLabelsUseKey(): void
    {
        $key = 'product';
        $factory = new PostTypeFactory();
        $postType = $factory->createPostType($key, []);
        $args = $postType->getArgs();
        $this->assertArrayHasKey('labels', $args);
        $this->assertSame($key, $args['labels']['name']);
        $this->assertSame($key, $args['labels']['singular_name']);
    }
}
