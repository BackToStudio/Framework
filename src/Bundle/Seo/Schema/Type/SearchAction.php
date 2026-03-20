<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class SearchAction extends SchemaType
{
    public function __construct()
    {
        parent::__construct('SearchAction');
    }

    /** @return $this */
    public function target(string $urlTemplate): static
    {
        return $this->set('target', $urlTemplate);
    }

    /** @return $this */
    public function queryInput(string $input): static
    {
        return $this->set('query-input', $input);
    }
}
