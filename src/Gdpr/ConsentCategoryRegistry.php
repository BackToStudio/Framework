<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Gdpr\Contracts\ConsentCategoryInterface;
use BackTo\Framework\Gdpr\Contracts\ConsentCategoryRegistryInterface;

final class ConsentCategoryRegistry implements RegistryInterface, ConsentCategoryRegistryInterface
{
    /** @var array<string, ConsentCategoryInterface> */
    private array $categories = [];

    public function add(ConsentCategoryInterface $category): ConsentCategoryRegistryInterface
    {
        $this->categories[$category->getKey()] = $category;

        return $this;
    }

    
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function get(string $key): ?ConsentCategoryInterface
    {
        return $this->categories[$key] ?? null;
    }
}
