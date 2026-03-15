<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose;

use BackTo\Framework\Contracts\SlugInterface;

trait HasSlug
{
    protected string $slug = '';

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): SlugInterface
    {
        $this->slug = $slug;
        return $this;
    }
}
