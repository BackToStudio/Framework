<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

final class OfferShippingDetails extends SchemaType
{
    public function __construct()
    {
        parent::__construct('OfferShippingDetails');
    }

    /** @return $this */
    public function shippingRate(SchemaType $rate): static
    {
        return $this->set('shippingRate', $rate);
    }

    /** @return $this */
    public function shippingDestination(SchemaType $destination): static
    {
        return $this->set('shippingDestination', $destination);
    }

    /** @return $this */
    public function deliveryTime(SchemaType $time): static
    {
        return $this->set('deliveryTime', $time);
    }
}
