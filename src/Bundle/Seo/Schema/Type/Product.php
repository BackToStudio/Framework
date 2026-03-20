<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class Product extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Product');
    }

    protected function getRequiredProperties(): array
    {
        return ['name', 'image', 'offers'];
    }

    /** @return $this */
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    /** @return $this */
    public function description(string $description): static
    {
        return $this->set('description', $description);
    }

    /** @return $this */
    public function image(string|SchemaType|array $image): static
    {
        return $this->set('image', $image);
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }

    /** @return $this */
    public function sku(string $sku): static
    {
        return $this->set('sku', $sku);
    }

    /** @return $this */
    public function gtin(string $gtin): static
    {
        return $this->set('gtin', $gtin);
    }

    /** @return $this */
    public function gtin13(string $gtin13): static
    {
        return $this->set('gtin13', $gtin13);
    }

    /** @return $this */
    public function mpn(string $mpn): static
    {
        return $this->set('mpn', $mpn);
    }

    /** @return $this */
    public function brand(SchemaType $brand): static
    {
        return $this->set('brand', $brand);
    }

    /** @return $this */
    public function color(string $color): static
    {
        return $this->set('color', $color);
    }

    /** @return $this */
    public function size(string $size): static
    {
        return $this->set('size', $size);
    }

    /** @return $this */
    public function material(string $material): static
    {
        return $this->set('material', $material);
    }

    
    public function offers(SchemaType|array $offers): static
    {
        return $this->set('offers', $offers);
    }

    /** @return $this */
    public function aggregateRating(SchemaType $rating): static
    {
        return $this->set('aggregateRating', $rating);
    }

    
    public function review(SchemaType|array $reviews): static
    {
        return $this->set('review', $reviews);
    }

    /** @return $this */
    public function category(string $category): static
    {
        return $this->set('category', $category);
    }
}
