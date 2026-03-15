<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Contracts;

interface PostTypeInterface
{
    public function getKey(): string;

    /**
     * @return array<string, mixed>
     */
    public function getArgs(): array;
}
