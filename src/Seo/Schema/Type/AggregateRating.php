<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

class AggregateRating extends SchemaType
{
    public function __construct()
    {
        parent::__construct('AggregateRating');
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

    /** @return $this */
    public function ratingCount(int $count): static
    {
        return $this->set('ratingCount', $count);
    }

    /** @return $this */
    public function reviewCount(int $count): static
    {
        return $this->set('reviewCount', $count);
    }
}
