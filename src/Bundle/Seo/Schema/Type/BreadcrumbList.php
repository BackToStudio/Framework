<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class BreadcrumbList extends SchemaType
{
    public function __construct()
    {
        parent::__construct('BreadcrumbList');
    }

    /**
     * @param SchemaType[] $items ListItem instances
     * @return $this
     */
    public function items(array $items): static
    {
        return $this->set('itemListElement', $items);
    }
}
