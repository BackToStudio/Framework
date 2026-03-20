<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\Contracts;

use BackTo\Framework\Contracts\IdInterface;
use BackTo\Framework\Contracts\ParentIdInterface;
use BackTo\Framework\Contracts\SlugInterface;

interface TermInterface extends IdInterface, SlugInterface, ParentIdInterface
{

    
    public function getName(): string;

    
    public function setName(string $name): TermInterface;

    
    public function getDescription(): string;

    
    public function setDescription(string $description): TermInterface;

    
    public function getTaxonomy(): string;

    
    public function setTaxonomy(string $taxonomy): TermInterface;

    public function isTopLevel(): bool;

    public function belongsTo(string $taxonomy): bool;

    public function hasDescription(): bool;
}
