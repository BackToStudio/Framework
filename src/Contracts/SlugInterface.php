<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

use BackTo\Framework\Contracts\Slug;

interface SlugInterface
{

    public function getSlug(): Slug;


    public function setSlug(Slug|string $slug): SlugInterface;

}
