<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Blocks;

use BackTo\Framework\Contracts\BlockStyleInterface;

abstract class CustomBlockStyle implements BlockStyleInterface
{

    /** @var string[] */
    protected array $blocks = [];

    protected string $styleName = '';

    protected string $label = '';

    
    public function getStyleName(): string
    {
        return $this->styleName;
    }

    
    public function getLabel(): string
    {
        return $this->label;
    }

    
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @return array{name: string, label: string}
     */
    public function getProperties(): array
    {
        return [
            'name'  => $this->getStyleName(),
            'label' => $this->getLabel()
        ];
    }
}
