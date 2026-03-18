<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

interface BlockStyleInterface
{
    /**
     * @return array{name: string, label: string}
     */
    public function getProperties(): array;

    
    public function getLabel(): string;

    
    public function getStyleName(): string;

    
    public function getBlocks(): array;
}
