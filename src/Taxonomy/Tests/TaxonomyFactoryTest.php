<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Tests;

use BackTo\Framework\Exception\InvalidTaxonomyException;
use BackTo\Framework\Taxonomy\Entity\Taxonomy;
use BackTo\Framework\Taxonomy\TaxonomyFactory;
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

    private function givenThereAreSpecificArgs(): array
    {
        return [
            'show_ui' => false,
            'show_in_rest' => false,
            'publicly_queryable' => false,
            'hierarchical' => false,
        ];
    }

    public function whenImCreatingTaxonomy(string $key, array $postTypes, array $args): Taxonomy
    {
        $factory = new TaxonomyFactory();
        return $factory->createTaxonomy($key, $postTypes, $args);
    }

    public function thenIShouldHaveDefaultArgs(array $args): void
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

    private function thenIShouldHaveSameArgs(array $args, array $givenArgs): void
    {
        $this->assertSame($args['show_ui'], $givenArgs['show_ui']);
        $this->assertSame($args['show_in_rest'], $givenArgs['show_in_rest']);
        $this->assertSame($args['publicly_queryable'], $givenArgs['publicly_queryable']);
        $this->assertSame($args['hierarchical'], $givenArgs['hierarchical']);
    }

    public function testNotAllowEmptyTaxonomyKey(): void
    {
        $this->expectException(InvalidTaxonomyException::class);
        $key = $this->givenThereIsEmptyKey();
        $args = $this->givenThereAreEmptyArgs();
        $this->whenImCreatingTaxonomy($key, ['abcdef'], $args);
    }

    public function testDefaultTaxonomyArgs(): void
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereAreEmptyArgs();
        $postType = $this->whenImCreatingTaxonomy($key, ['abcdef'], $args);
        $this->thenIShouldHaveDefaultArgs($postType->getArgs());
    }

    public function testGivenTaxonomyArgs(): void
    {
        $key = $this->givenThereIsKey();
        $args = $this->givenThereAreSpecificArgs();
        $postType = $this->whenImCreatingTaxonomy($key, ['abcdef'], $args);
        $this->thenIShouldHaveSameArgs($postType->getArgs(), $args);
    }

    public function testDefaultLabelsUseKey(): void
    {
        $key = 'category';
        $factory = new TaxonomyFactory();
        $taxonomy = $factory->createTaxonomy($key, ['post'], []);
        $args = $taxonomy->getArgs();
        $this->assertArrayHasKey('labels', $args);
        $this->assertSame($key, $args['labels']['name']);
        $this->assertSame($key, $args['labels']['singular_name']);
    }
}
