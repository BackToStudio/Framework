<?php

namespace Fantassin\Core\WordPress\PostType\Contracts;

interface PostTypeInterface
{

    /**
     * @return string
     */
    public function getKey(): ?string;

    /**
     * @return array
     */
    public function getArgs(): array;
}
