<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

final class Clip extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Clip');
    }

    /** @return $this */
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    /** @return $this */
    public function startOffset(int $seconds): static
    {
        return $this->set('startOffset', $seconds);
    }

    /** @return $this */
    public function endOffset(int $seconds): static
    {
        return $this->set('endOffset', $seconds);
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }
}
