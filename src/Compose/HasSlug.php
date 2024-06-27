<?php

namespace BackTo\Framework\Compose;

use BackTo\Framework\Contracts\SlugInterface;

trait HasSlug
{

    /**
     * @var string
     */
    protected $slug = '';

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return SlugInterface
     */
    public function setSlug(string $slug): SlugInterface
    {
        $this->slug = $slug;
        return $this;
    }

}
