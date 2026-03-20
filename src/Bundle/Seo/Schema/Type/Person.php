<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class Person extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Person');
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
    public function email(string $email): static
    {
        return $this->set('email', $email);
    }

    /** @return $this */
    public function image(string|SchemaType $image): static
    {
        return $this->set('image', $image);
    }

    
    public function sameAs(array $urls): static
    {
        return $this->set('sameAs', $urls);
    }
}
