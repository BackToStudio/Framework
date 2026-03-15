<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Contracts;

interface ConsentCategoryRegistryInterface
{
    public function add(ConsentCategoryInterface $category): self;

    /**
     * @return ConsentCategoryInterface[]
     */
    public function getCategories(): array;

    public function get(string $key): ?ConsentCategoryInterface;
}
