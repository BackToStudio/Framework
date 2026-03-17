<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

final class Article extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Article');
    }

    protected function getRequiredProperties(): array
    {
        return ['headline', 'author', 'datePublished'];
    }

    /** @return $this */
    public function headline(string $headline): static
    {
        return $this->set('headline', $headline);
    }

    /** @return $this */
    public function description(string $description): static
    {
        return $this->set('description', $description);
    }

    /** @return $this */
    public function author(SchemaType|string $author): static
    {
        return $this->set('author', $author);
    }

    /** @return $this */
    public function publisher(SchemaType $publisher): static
    {
        return $this->set('publisher', $publisher);
    }

    /** @return $this */
    public function datePublished(string $date): static
    {
        return $this->set('datePublished', $date);
    }

    /** @return $this */
    public function dateModified(string $date): static
    {
        return $this->set('dateModified', $date);
    }

    /** @return $this */
    public function image(string|SchemaType $image): static
    {
        return $this->set('image', $image);
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }

    /** @return $this */
    public function mainEntityOfPage(string|SchemaType $page): static
    {
        return $this->set('mainEntityOfPage', $page);
    }

    /** @return $this */
    public function articleSection(string $section): static
    {
        return $this->set('articleSection', $section);
    }

    
    public function keywords(array $keywords): static
    {
        return $this->set('keywords', $keywords);
    }

    /** @return $this */
    public function wordCount(int $count): static
    {
        return $this->set('wordCount', $count);
    }
}
