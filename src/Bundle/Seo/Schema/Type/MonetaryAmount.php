<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class MonetaryAmount extends SchemaType
{
    public function __construct()
    {
        parent::__construct('MonetaryAmount');
    }

    /** @return $this */
    public function currency(string $currency): static
    {
        return $this->set('currency', $currency);
    }

    /** @return $this */
    public function value(float|int|SchemaType $value): static
    {
        return $this->set('value', $value);
    }

    /** @return $this */
    public function minValue(float|int $value): static
    {
        return $this->set('minValue', $value);
    }

    /** @return $this */
    public function maxValue(float|int $value): static
    {
        return $this->set('maxValue', $value);
    }
}
