<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

final class Answer extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Answer');
    }

    /** @return $this */
    public function text(string $text): static
    {
        return $this->set('text', $text);
    }
}
