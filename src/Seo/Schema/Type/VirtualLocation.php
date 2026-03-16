<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

class VirtualLocation extends SchemaType
{
    public function __construct()
    {
        parent::__construct('VirtualLocation');
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }
}
