<?php

namespace Fantassin\Core\WordPress\Taxonomy;

use Exception;
use Fantassin\Core\WordPress\Taxonomy\Contracts\TaxonomyInterface;
use Fantassin\Core\WordPress\Taxonomy\Entity\Taxonomy;

class TaxonomyFactory
{
    /**
     * @param string $key
     * @param string[] $postTypes
     * @param array $args
     *
     * @return Taxonomy
     * @throws Exception
     */
    public function createTaxonomy(string $key, array $postTypes, array $args): TaxonomyInterface
    {
        if (empty($key)) {
            throw new Exception(
                'WordPress required taxonomy name. (max. 20 characters, cannot contain capital letters, underscores or spaces)'
            );
        }

        $args = $this->prepareDefaultArgs($args);

        // Add arbitrary labels if no exists.
        $args = $this->addArgIfNotExist(
            $args,
            'labels',
            [
                'name' => $this->getPluralName($key),
                'singular_name' => $this->getSingularName($key),
            ]
        );

        $taxonomy = new Taxonomy();
        $taxonomy
            ->setKey($key)
            ->setPostTypes($postTypes)
            ->setArgs($args);

        return $taxonomy;
    }

    /**
     * @param array $args
     *
     * @return array
     */
    private function prepareDefaultArgs(array $args): array
    {
        $args = $this->addArgIfNotExist($args, 'show_ui', true);
        $args = $this->addArgIfNotExist($args, 'show_in_rest', true);
        $args = $this->addArgIfNotExist($args, 'publicly_queryable', true);
        $args = $this->addArgIfNotExist($args, 'hierarchical', true);

        return $args;
    }

    /**
     * @param array $args
     * @param string $key
     * @param $value
     *
     * @return array
     */
    private function addArgIfNotExist(array $args, string $key, $value): array
    {
        if (!\array_key_exists($key, $args)) {
            $args[$key] = $value;
        }

        return $args;
    }

    public function getSingularName(string $key): string
    {
        $name = str_replace('-', ' ', $key);
        $name = str_replace('_', ' ', $name);
        return ucwords($name);
    }

    public function getPluralName(string $key): string
    {
        return $this->getSingularName($key) . 's';
    }
}
