<?php

namespace Fantassin\Core\WordPress\Taxonomy\Tests;

use Exception;
use Fantassin\Core\WordPress\Taxonomy\Entity\Taxonomy;
use Fantassin\Core\WordPress\Taxonomy\TaxonomyFactory;
use PHPUnit\Framework\TestCase;

class TaxonomyFactoryTest extends TestCase
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
            'publicly_queryable' => false,
            'hierarchical' => false
        ];
    }

    /**
     * @throws Exception
     */
    public function whenImCreatingTaxonomy(string $key, array $postTypes, array $args): Taxonomy
    {
        $factory = new TaxonomyFactory();
        return $factory->createTaxonomy($key, $postTypes, $args);
    }

    /**
     * @throws Exception
     */
    public function thenIShouldHaveDefaultArgs(array $args)
    {
        $this->assertArrayHasKey('show_ui', $args);
        $this->assertArrayHasKey('show_in_rest', $args);
        $this->assertArrayHasKey('publicly_queryable', $args);
        $this->assertArrayHasKey('hierarchical', $args);
        $this->assertTrue($args['show_ui']);
        $this->assertTrue($args['show_in_rest']);
        $this->assertTrue($args['publicly_queryable']);
        $this->assertTrue($args['hierarchical']);
    }

    private function thenIShouldHaveSameArgs(array $args, array $givenArgs)
    {
        $this->assertSame($args['show_ui'], $givenArgs['show_ui']);
        $this->assertSame($args['show_in_rest'], $givenArgs['show_in_rest']);
        $this->assertSame($args['publicly_queryable'], $givenArgs['publicly_queryable']);
        $this->assertSame($args['hierarchical'], $givenArgs['hierarchical']);
    }


    /**
     * @throws Exception
     */
    public function testNotAllowEmptyTaxonomyKey()
    {
        $this->expectException(Exception::class);
        $key = $this->givenThereIsEmptyKey();
        $args = $this->givenThereAreEmptyArgs();
        $this->whenImCreatingTaxonomy($key, ['abcdef'], $args);
    }

    /**
     * @throws Exception
     */
    public function testDefaultTaxonomyArgs()
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereAreEmptyArgs();
        $postType = $this->whenImCreatingTaxonomy($key, ['abcdef'], $args);
        $this->thenIShouldHaveDefaultArgs($postType->getArgs());
    }

    /**
     * @throws Exception
     */
    public function testGivenTaxonomyArgs()
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereAreSpecificArgs();
        $postType = $this->whenImCreatingTaxonomy($key, ['abcdef'], $args);
        $this->thenIShouldHaveSameArgs($postType->getArgs(), $args);
    }

    /**
     * @throws Exception
     */
    public function testTaxonomyNames()
    {
        $key = 'abc-def';
        $factory = new TaxonomyFactory();
        $this->assertSame('Abc Def', $factory->getSingularName($key));
        $this->assertSame('Abc Defs', $factory->getPluralName($key));
    }

}
