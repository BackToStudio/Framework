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

    
    public function getPostTypes(): array;

    
    public function setPostTypes(array $postTypes): TaxonomyInterface;

    /**
     * @param array<string, mixed> $args
     */
    public function setArgs(array $args): TaxonomyInterface;
}
