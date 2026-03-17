<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy;

use BackTo\Framework\Exception\InvalidTaxonomyException;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;
use BackTo\Framework\Taxonomy\Entity\Taxonomy;

final class TaxonomyFactory
{
    /**
     * @param string[] $postTypes
     * @param array<string, mixed> $args
     *
     * @throws InvalidTaxonomyException
     */
    public function createTaxonomy(string $key, array $postTypes, array $args): TaxonomyInterface
    {
        if (empty($key)) {
            throw InvalidTaxonomyException::emptyKey();
        }

        $args = $this->prepareDefaultArgs($args);

        // Add arbitrary labels if none exist.
        $args = $this->addArgIfNotExist(
            $args,
            'labels',
            [
                'name' => $key,
                'singular_name' => $key,
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
     * @param array<string, mixed> $args
     * @return array<string, mixed>
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
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function addArgIfNotExist(array $args, string $key, mixed $value): array
    {
        if (!\array_key_exists($key, $args)) {
            $args[$key] = $value;
        }

        return $args;
    }
}
