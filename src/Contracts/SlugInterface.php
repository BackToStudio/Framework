<?php

namespace BackTo\Framework\Contracts;

interface SlugInterface
{
    /**
     * @return string
     */
    public function getSlug(): string;

    /**
     * @param string $slug
     * @return SlugInterface
     */
    public function setSlug(string $slug): SlugInterface;

}
