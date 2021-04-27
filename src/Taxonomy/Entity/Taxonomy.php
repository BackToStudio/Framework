<?php

namespace Fantassin\Core\WordPress\Taxonomy\Entity;

use Fantassin\Core\WordPress\Taxonomy\TaxonomyInterface;

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
     * @return Taxonomy
     */
    public function setKey(string $key): Taxonomy
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
     * @return Taxonomy
     */
    public function setArgs(array $args): Taxonomy
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
    public function setPostTypes(array $postTypes): Taxonomy
    {
        $this->postTypes = $postTypes;

        return $this;
    }

    /**
     * @param string $postType
     *
     * @return Taxonomy
     */
    public function addPostType(string $postType): Taxonomy
    {
        $this->postTypes[] = $postType;

        return $this;
    }
}
