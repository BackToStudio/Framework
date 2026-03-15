<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Contracts;

/**
 * Port interface for taxonomy registration.
 *
 * Abstracts WordPress register_taxonomy() and related functions,
 * allowing the domain layer to remain platform-agnostic.
 */
interface TaxonomyRegistrarInterface
{
    /**
     * @param string $key
     * @param string|string[] $objectType
     * @param array<string, mixed> $args
     */
    public function register(string $key, $objectType, array $args): void;

    public function exists(string $key): bool;

    public function flushRewriteRules(): void;
}
