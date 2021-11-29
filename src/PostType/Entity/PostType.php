<?php

namespace Fantassin\Core\WordPress\PostType\Entity;

use Fantassin\Core\WordPress\PostType\Contracts\PostTypeInterface;

class PostType implements PostTypeInterface
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
     * @return string
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return PostType
     */
    public function setKey(string $key): PostTypeInterface
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
     *
     * @return PostType
     */
    public function setArgs(array $args): PostTypeInterface
    {
        $this->args = $args;

        return $this;
    }
}
