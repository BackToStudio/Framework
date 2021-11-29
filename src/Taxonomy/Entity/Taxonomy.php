<?php

namespace Fantassin\Core\WordPress\Taxonomy\Entity;

use Fantassin\Core\WordPress\Taxonomy\Contracts\TaxonomyInterface;

class Taxonomy implements TaxonomyInterface
{

    /**
     * @var string
     */
    private $key;

    /**
     * @var array
     */
    private $args = [];

    /**
     * @var string[]
     */
    private $postTypes = [];

    /**
     * @return string
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return TaxonomyInterface
     */
    public function setKey(string $key): TaxonomyInterface
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param array $args
     * @return TaxonomyInterface
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
     * @param array $postTypes
     * @return Taxonomy
     */
    public function setPostTypes(array $postTypes): TaxonomyInterface
    {
        $this->postTypes = $postTypes;

        return $this;
    }

    /**
     * @param string $postType
     *
     * @return Taxonomy
     */
    public function addPostType(string $postType): TaxonomyInterface
    {
        $this->postTypes[] = $postType;

        return $this;
    }
}
