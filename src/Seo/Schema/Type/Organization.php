<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

final class Organization extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Organization');
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

    
    public function sameAs(array $urls): static
    {
        return $this->set('sameAs', $urls);
    }

    /** @return $this */
    public function description(string $description): static
    {
        return $this->set('description', $description);
    }

    /** @return $this */
    public function email(string $email): static
    {
        return $this->set('email', $email);
    }

    /** @return $this */
    public function telephone(string $telephone): static
    {
        return $this->set('telephone', $telephone);
    }

    /** @return $this */
    public function address(SchemaType $address): static
    {
        return $this->set('address', $address);
    }
}
