<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Entity;

use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;

final class Taxonomy implements TaxonomyInterface
{
    private string $key = '';

    /** @var array<string, mixed> */
    private array $args = [];

    /** @var string[] */
    private array $postTypes = [];

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): TaxonomyInterface
    {
        if ($key !== '' && \strlen($key) > 32) {
            throw new \InvalidArgumentException(
                \sprintf('Taxonomy key cannot exceed 32 characters, got %d.', \strlen($key))
            );
        }

        $this->key = $key;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function setArgs(array $args): TaxonomyInterface
    {
        $this->args = $args;

        return $this;
    }

    
    public function getPostTypes(): array
    {
        return $this->postTypes;
    }

    
    public function setPostTypes(array $postTypes): TaxonomyInterface
    {
        $this->postTypes = $postTypes;

        return $this;
    }

    public function addPostType(string $postType): TaxonomyInterface
    {
        if (!in_array($postType, $this->postTypes, true)) {
            $this->postTypes[] = $postType;
        }

        return $this;
    }

    // ── Domain Logic ────────────────────────────────────────

    /**
     * Whether this taxonomy is hierarchical (like categories).
     */
    public function isHierarchical(): bool
    {
        return (bool) ($this->args['hierarchical'] ?? false);
    }

    /**
     * Whether this taxonomy is exposed in the REST API.
     */
    public function isExposedInRest(): bool
    {
        return (bool) ($this->args['show_in_rest'] ?? false);
    }

    /**
     * Whether this taxonomy is publicly queryable.
     */
    public function isPubliclyQueryable(): bool
    {
        return (bool) ($this->args['publicly_queryable'] ?? false);
    }

    /**
     * Whether the taxonomy key has been set.
     */
    public function hasKey(): bool
    {
        return $this->key !== '';
    }

    /**
     * Whether this taxonomy is attached to a given post type.
     */
    public function isAttachedTo(string $postType): bool
    {
        return \in_array($postType, $this->postTypes, true);
    }

    /**
     * Remove a post type association.
     */
    public function removePostType(string $postType): TaxonomyInterface
    {
        $this->postTypes = array_values(
            array_filter($this->postTypes, static fn (string $pt): bool => $pt !== $postType)
        );

        return $this;
    }
}
