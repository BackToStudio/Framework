<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

final class ImageObject extends SchemaType
{
    public function __construct()
    {
        parent::__construct('ImageObject');
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }

    /** @return $this */
    public function width(int $width): static
    {
        return $this->set('width', $width);
    }

    /** @return $this */
    public function height(int $height): static
    {
        return $this->set('height', $height);
    }

    /** @return $this */
    public function caption(string $caption): static
    {
        return $this->set('caption', $caption);
    }
}
