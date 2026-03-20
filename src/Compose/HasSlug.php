<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose;

use BackTo\Framework\Contracts\Slug;
use BackTo\Framework\Contracts\SlugInterface;

trait HasSlug
{
    protected Slug $slug;

    public function getSlug(): Slug
    {
        if (!isset($this->slug)) {
            $this->slug = new Slug('');
        }
        return $this->slug;
    }

    public function setSlug(Slug|string $slug): SlugInterface
    {
        $this->slug = $slug instanceof Slug ? $slug : new Slug($slug);
        return $this;
    }
}
