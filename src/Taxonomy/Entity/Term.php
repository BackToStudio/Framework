<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Entity;

use BackTo\Framework\Compose\HasId;
use BackTo\Framework\Compose\HasParentId;
use BackTo\Framework\Compose\HasSlug;
use BackTo\Framework\Taxonomy\Contracts\TermInterface;

final class Term implements TermInterface
{
    use HasId;
    use HasSlug;
    use HasParentId;

    private string $name = '';
    private string $description = '';
    private string $taxonomy = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): TermInterface
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Term name cannot be empty.');
        }

        $this->name = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): TermInterface
    {
        $this->description = $description;
        return $this;
    }

    public function getTaxonomy(): string
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(string $taxonomy): TermInterface
    {
        if (trim($taxonomy) === '') {
            throw new \InvalidArgumentException('Taxonomy name cannot be empty.');
        }

        $this->taxonomy = $taxonomy;
        return $this;
    }

    // ── Domain Logic ────────────────────────────────────────

    /**
     * Whether this term is a root term (has no parent).
     */
    public function isTopLevel(): bool
    {
        return $this->getParentId() === null || $this->getParentId() === 0;
    }

    /**
     * Whether this term belongs to a given taxonomy.
     */
    public function belongsTo(string $taxonomy): bool
    {
        return $this->taxonomy === $taxonomy;
    }

    /**
     * Whether this term has a description.
     */
    public function hasDescription(): bool
    {
        return trim($this->description) !== '';
    }
}
