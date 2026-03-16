<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

class WebSite extends SchemaType
{
    public function __construct()
    {
        parent::__construct('WebSite');
    }

    /** @return $this */
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }

    /** @return $this */
    public function description(string $description): static
    {
        return $this->set('description', $description);
    }

    /** @return $this */
    public function publisher(SchemaType $publisher): static
    {
        return $this->set('publisher', $publisher);
    }

    /** @return $this */
    public function inLanguage(string $language): static
    {
        return $this->set('inLanguage', $language);
    }

    /** @return $this */
    public function potentialAction(SchemaType $action): static
    {
        return $this->set('potentialAction', $action);
    }
}
