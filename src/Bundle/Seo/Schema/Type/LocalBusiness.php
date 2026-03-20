<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class LocalBusiness extends SchemaType
{
    public function __construct()
    {
        parent::__construct('LocalBusiness');
    }

    protected function getRequiredProperties(): array
    {
        return ['name', 'address'];
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

    /** @return $this */
    public function image(string|SchemaType $image): static
    {
        return $this->set('image', $image);
    }

    /** @return $this */
    public function description(string $description): static
    {
        return $this->set('description', $description);
    }

    /** @return $this */
    public function telephone(string $telephone): static
    {
        return $this->set('telephone', $telephone);
    }

    /** @return $this */
    public function email(string $email): static
    {
        return $this->set('email', $email);
    }

    /** @return $this */
    public function address(SchemaType $address): static
    {
        return $this->set('address', $address);
    }

    /** @return $this */
    public function priceRange(string $priceRange): static
    {
        return $this->set('priceRange', $priceRange);
    }

    /** @return $this */
    public function openingHours(string $openingHours): static
    {
        return $this->set('openingHours', $openingHours);
    }

    /** @return $this */
    public function geo(SchemaType $geo): static
    {
        return $this->set('geo', $geo);
    }

    
    public function sameAs(array $urls): static
    {
        return $this->set('sameAs', $urls);
    }
}
