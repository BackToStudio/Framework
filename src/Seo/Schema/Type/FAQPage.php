<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

final class FAQPage extends SchemaType
{
    public function __construct()
    {
        parent::__construct('FAQPage');
    }

    protected function getRequiredProperties(): array
    {
        return ['mainEntity'];
    }

    /**
     * @param SchemaType[] $questions Question instances
     * @return $this
     */
    public function mainEntity(array $questions): static
    {
        return $this->set('mainEntity', $questions);
    }
}
