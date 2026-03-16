<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

class Place extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Place');
    }

    /** @return $this */
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    /** @return $this */
    public function address(SchemaType|string $address): static
    {
        return $this->set('address', $address);
    }

    /** @return $this */
    public function geo(SchemaType $geo): static
    {
        return $this->set('geo', $geo);
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }
}
