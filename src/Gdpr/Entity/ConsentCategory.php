<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Entity;

use BackTo\Framework\Gdpr\Contracts\ConsentCategoryInterface;

final class ConsentCategory implements ConsentCategoryInterface
{
    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private readonly string $description = '',
        private readonly bool $required = false,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
