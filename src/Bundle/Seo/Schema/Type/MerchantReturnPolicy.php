<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class MerchantReturnPolicy extends SchemaType
{
    public function __construct()
    {
        parent::__construct('MerchantReturnPolicy');
    }

    /** @return $this */
    public function applicableCountry(string $country): static
    {
        return $this->set('applicableCountry', $country);
    }

    /** @return $this */
    public function returnPolicyCategory(string $category): static
    {
        return $this->set('returnPolicyCategory', $category);
    }

    /** @return $this */
    public function merchantReturnDays(int $days): static
    {
        return $this->set('merchantReturnDays', $days);
    }

    /** @return $this */
    public function returnMethod(string $method): static
    {
        return $this->set('returnMethod', $method);
    }

    /** @return $this */
    public function returnFees(string $fees): static
    {
        return $this->set('returnFees', $fees);
    }
}
