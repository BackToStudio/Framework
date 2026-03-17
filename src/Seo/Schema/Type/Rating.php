<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

final class Rating extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Rating');
    }

    /** @return $this */
    public function ratingValue(float|string $value): static
    {
        return $this->set('ratingValue', $value);
    }

    /** @return $this */
    public function bestRating(float|string $value): static
    {
        return $this->set('bestRating', $value);
    }

    /** @return $this */
    public function worstRating(float|string $value): static
    {
        return $this->set('worstRating', $value);
    }
}
