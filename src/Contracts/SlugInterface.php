<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

interface SlugInterface
{
    
    public function getSlug(): string;

    
    public function setSlug(string $slug): SlugInterface;

}
