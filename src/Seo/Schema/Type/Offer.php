<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

final class Offer extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Offer');
    }

    protected function getRequiredProperties(): array
    {
        return ['price', 'priceCurrency'];
    }

    /** @return $this */
    public function price(string|float|int $price): static
    {
        return $this->set('price', $price);
    }

    /** @return $this */
    public function priceCurrency(string $currency): static
    {
        return $this->set('priceCurrency', $currency);
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }

    /** @return $this */
    public function availability(string $availability): static
    {
        return $this->set('availability', $availability);
    }

    /** @return $this */
    public function validFrom(string $date): static
    {
        return $this->set('validFrom', $date);
    }

    /** @return $this */
    public function validThrough(string $date): static
    {
        return $this->set('validThrough', $date);
    }

    /** @return $this */
    public function category(string $category): static
    {
        return $this->set('category', $category);
    }
}
