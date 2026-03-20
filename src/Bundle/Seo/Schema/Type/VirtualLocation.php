<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class VirtualLocation extends SchemaType
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
