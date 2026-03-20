<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Entity;

use BackTo\Framework\PostType\Contracts\PostTypeInterface;

final class PostType implements PostTypeInterface
{
    private string $key = '';

    /** @var array<string, mixed> */
    private array $args = [];

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): PostTypeInterface
    {
        if ($key !== '' && \strlen($key) > 20) {
            throw new \InvalidArgumentException(
                \sprintf('Post type key cannot exceed 20 characters, got %d.', \strlen($key))
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
    public function setArgs(array $args): PostTypeInterface
    {
        $this->args = $args;

        return $this;
    }

    // ── Domain Logic ────────────────────────────────────────

    /**
     * Whether this post type is hierarchical (like pages).
     */
    public function isHierarchical(): bool
    {
        return (bool) ($this->args['hierarchical'] ?? false);
    }

    /**
     * Whether this post type is exposed in the REST API.
     */
    public function isExposedInRest(): bool
    {
        return (bool) ($this->args['show_in_rest'] ?? false);
    }

    /**
     * Whether this post type is publicly queryable.
     */
    public function isPublic(): bool
    {
        return (bool) ($this->args['public'] ?? false);
    }

    /**
     * Whether the post type supports a specific feature (e.g. 'title', 'editor', 'thumbnail').
     */
    public function supports(string $feature): bool
    {
        $supports = $this->args['supports'] ?? [];

        return \is_array($supports) && \in_array($feature, $supports, true);
    }

    /**
     * Whether the post type key has been set.
     */
    public function hasKey(): bool
    {
        return $this->key !== '';
    }
}
