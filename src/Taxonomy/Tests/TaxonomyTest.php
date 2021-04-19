<?php

namespace Fantassin\Core\WordPress\Taxonomy\Tests;

use Fantassin\Core\WordPress\Taxonomy\Entity\Taxonomy;
use PHPUnit\Framework\TestCase;

class TaxonomyTest extends TestCase
{

    public function testKey()
    {
        $taxonomy = new Taxonomy();
        $this->assertNull($taxonomy->getKey());
        $taxonomy->setKey('abcde');
        $this->assertSame('abcde', $taxonomy->getKey());
    }

    public function testArgs()
    {
        $taxonomy = new Taxonomy();
        $this->assertCount(0, $taxonomy->getArgs());
        $args = [
            'labels' => []
        ];
        $taxonomy->setArgs($args);
        $this->assertArrayHasKey('labels', $taxonomy->getArgs());
    }

    public function testAddingPostTypes()
    {
        $taxonomy = new Taxonomy();
        $this->assertCount(0, $taxonomy->getPostTypes());
        $taxonomy->addPostType( 'abcde' );
        $taxonomy->addPostType( 'fghijk' );
        $this->assertContains('abcde', $taxonomy->getPostTypes());
        $this->assertContains('fghijk', $taxonomy->getPostTypes());
    }
}
