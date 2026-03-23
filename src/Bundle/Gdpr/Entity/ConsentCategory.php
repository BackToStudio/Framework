<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Entity;

use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentCategoryInterface;

final class ConsentCategory implements ConsentCategoryInterface
{
    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private string $description = '',
        private bool $required = false,
    ) {
        if ($key === '') {
            throw new \InvalidArgumentException('Consent category key cannot be empty.');
        }

        if ($label === '') {
            throw new \InvalidArgumentException('Consent category label cannot be empty.');
        }
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
