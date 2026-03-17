<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

class Brand extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Brand');
    }

    /** @return $this */
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }

    /** @return $this */
    public function logo(string|SchemaType $logo): static
    {
        return $this->set('logo', $logo);
    }
}
