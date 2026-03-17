<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

class PostalAddress extends SchemaType
{
    public function __construct()
    {
        parent::__construct('PostalAddress');
    }

    /** @return $this */
    public function streetAddress(string $street): static
    {
        return $this->set('streetAddress', $street);
    }

    /** @return $this */
    public function addressLocality(string $locality): static
    {
        return $this->set('addressLocality', $locality);
    }

    /** @return $this */
    public function addressRegion(string $region): static
    {
        return $this->set('addressRegion', $region);
    }

    /** @return $this */
    public function postalCode(string $postalCode): static
    {
        return $this->set('postalCode', $postalCode);
    }

    /** @return $this */
    public function addressCountry(string $country): static
    {
        return $this->set('addressCountry', $country);
    }
}
