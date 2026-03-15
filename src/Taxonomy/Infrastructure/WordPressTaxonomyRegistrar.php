<?php

namespace BackTo\Framework\Taxonomy\Infrastructure;

use BackTo\Framework\Taxonomy\Contracts\TaxonomyRegistrarInterface;

use function flush_rewrite_rules;
use function register_taxonomy;
use function taxonomy_exists;

/**
 * WordPress adapter for taxonomy registration.
 */
class WordPressTaxonomyRegistrar implements TaxonomyRegistrarInterface
{
    /**
     * @param string $key
     * @param string|string[] $objectType
     * @param array<string, mixed> $args
     */
    public function register(string $key, $objectType, array $args): void
    {
        register_taxonomy($key, $objectType, $args);
    }

    public function exists(string $key): bool
    {
        return taxonomy_exists($key);
    }

    public function flushRewriteRules(): void
    {
        flush_rewrite_rules();
    }
}
