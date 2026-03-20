<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Contracts;

interface ConsentCategoryRegistryInterface
{
    public function add(ConsentCategoryInterface $category): self;

    
    public function getCategories(): array;

    public function get(string $key): ?ConsentCategoryInterface;
}
