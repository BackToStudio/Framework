<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Contracts;

interface TaxonomyInterface
{
    public function getKey(): string;

    /**
     * @return array<string, mixed>
     */
    public function getArgs(): array;

    /**
     * @return string[]
     */
    public function getPostTypes(): array;
}
