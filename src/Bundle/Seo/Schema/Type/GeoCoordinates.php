<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class GeoCoordinates extends SchemaType
{
    public function __construct()
    {
        parent::__construct('GeoCoordinates');
    }

    /** @return $this */
    public function latitude(float $latitude): static
    {
        return $this->set('latitude', $latitude);
    }

    /** @return $this */
    public function longitude(float $longitude): static
    {
        return $this->set('longitude', $longitude);
    }
}
