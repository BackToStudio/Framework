<?php

namespace Fantassin\Core\WordPress\Blocks;

use Fantassin\Core\WordPress\Contracts\BlockStyleInterface;

abstract class CustomBlockStyle implements BlockStyleInterface
{

    protected $blocks = [];

    protected $styleName = '';

    protected $label = '';

    /**
     * @return string
     */
    public function getStyleName(): string
    {
        return $this->styleName;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function getProperties(): array
    {
        return [
            'name'  => $this->getStyleName(),
            'label' => $this->getLabel()
        ];
    }
}
