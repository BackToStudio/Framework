<?php

namespace Fantassin\Core\WordPress\PostType\Tests;

use Exception;
use Fantassin\Core\WordPress\PostType\Entity\PostType;
use Fantassin\Core\WordPress\PostType\PostTypeFactory;
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

    private function givenThereAreSpecificArgs()
    {
        return [
            'show_ui' => false,
            'show_in_rest' => false,
            'publicly_queryable' => false
        ];
    }


    private function givenThereIsHierarchicalPostTypeArgs()
    {
        return [
            'hierarchical' => true,
        ];
    }


    private function givenThereAreEditorSupports()
    {
        return [
            'supports' => ['editor'],
        ];
    }

    /**
     * @throws Exception
     */
    public function whenImCreatingPostType(string $key, array $args): PostType
    {
        $factory = new PostTypeFactory();
        return $factory->createPostType($key, $args);
    }

    /**
     * @throws Exception
     */
    public function thenIShouldHaveDefaultArgs(array $args)
    {
        $this->assertArrayHasKey('show_ui', $args);
        $this->assertArrayHasKey('show_in_rest', $args);
        $this->assertArrayHasKey('publicly_queryable', $args);
        $this->assertTrue($args['show_ui']);
        $this->assertTrue($args['show_in_rest']);
        $this->assertTrue($args['publicly_queryable']);
    }

    /**
     * @throws Exception
     */
    public function thenIShouldHaveHierarchicalPostTypeSupportsExist(array $args)
    {
        $this->assertArrayHasKey('hierarchical', $args);
        $this->assertTrue($args['hierarchical']);
    }

    private function thenIShouldHaveSameArgs(array $args, array $givenArgs)
    {
        $this->assertSame($args['show_ui'], $givenArgs['show_ui']);
        $this->assertSame($args['show_in_rest'], $givenArgs['show_in_rest']);
        $this->assertSame($args['publicly_queryable'], $givenArgs['publicly_queryable']);
    }

    private function thenIShouldHaveCustomFieldSupports(array $args)
    {
        $this->assertArrayHasKey('supports', $args);
        $this->assertContains('custom-fields', $args['supports']);
    }

    private function thenIShouldHaveRevisionsSupports(array $args)
    {
        $this->assertArrayHasKey('supports', $args);
        $this->assertContains('revisions', $args['supports']);
    }

    private function thenIShouldHaveTitleSupports(array $args)
    {
        $this->assertArrayHasKey('supports', $args);
        $this->assertContains('title', $args['supports']);
    }


    /**
     * @throws Exception
     */
    public function testNotAllowEmptyPostTypeKey()
    {
        $this->expectException(Exception::class);
        $key = $this->givenThereIsEmptyKey();
        $args = $this->givenThereAreEmptyArgs();
        $this->whenImCreatingPostType($key, $args);
    }

    /**
     * @throws Exception
     */
    public function testDefaultPostTypeArgs()
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereAreEmptyArgs();
        $postType = $this->whenImCreatingPostType($key, $args);
        $this->thenIShouldHaveDefaultArgs($postType->getArgs());
    }

    /**
     * @throws Exception
     */
    public function testHierarchicalPostType()
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereIsHierarchicalPostTypeArgs();
        $postType = $this->whenImCreatingPostType($key, $args);
        $this->thenIShouldHaveHierarchicalPostTypeSupportsExist($postType->getArgs());
    }

    /**
     * @throws Exception
     */
    public function testGivenPostTypeArgs()
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereAreSpecificArgs();
        $postType = $this->whenImCreatingPostType($key, $args);
        $this->thenIShouldHaveSameArgs($postType->getArgs(), $args);
    }

    /**
     * @throws Exception
     */
    public function testPostTypeEditorSupports()
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereAreEditorSupports();
        $postType = $this->whenImCreatingPostType($key, $args);
        $this->thenIShouldHaveCustomFieldSupports($postType->getArgs());
        $this->thenIShouldHaveRevisionsSupports($postType->getArgs());
        $this->thenIShouldHaveTitleSupports($postType->getArgs());
    }

    /**
     * @throws Exception
     */
    public function testPostTypeNames()
    {
        $key = 'abc-def';
        $factory = new PostTypeFactory();
        $this->assertSame('Abc Def', $factory->getSingularName($key));
        $this->assertSame('Abc Defs', $factory->getPluralName($key));
    }

}
