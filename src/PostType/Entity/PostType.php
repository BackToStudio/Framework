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
}
