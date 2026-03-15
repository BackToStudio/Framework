<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Entity;

use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;

class Taxonomy implements TaxonomyInterface
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

    /**
     * @return string[]
     */
    public function getPostTypes(): array
    {
        return $this->postTypes;
    }

    /**
     * @param string[] $postTypes
     */
    public function setPostTypes(array $postTypes): TaxonomyInterface
    {
        $this->postTypes = $postTypes;

        return $this;
    }

    public function addPostType(string $postType): TaxonomyInterface
    {
        $this->postTypes[] = $postType;

        return $this;
    }
}
