<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class Review extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Review');
    }

    protected function getRequiredProperties(): array
    {
        return ['itemReviewed', 'reviewRating', 'author'];
    }

    /** @return $this */
    public function itemReviewed(SchemaType $item): static
    {
        return $this->set('itemReviewed', $item);
    }

    /** @return $this */
    public function reviewRating(SchemaType $rating): static
    {
        return $this->set('reviewRating', $rating);
    }

    /** @return $this */
    public function author(SchemaType|string $author): static
    {
        return $this->set('author', $author);
    }

    /** @return $this */
    public function datePublished(string $date): static
    {
        return $this->set('datePublished', $date);
    }

    /** @return $this */
    public function reviewBody(string $body): static
    {
        return $this->set('reviewBody', $body);
    }

    /** @return $this */
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    /** @return $this */
    public function publisher(SchemaType $publisher): static
    {
        return $this->set('publisher', $publisher);
    }
}
