<?php

namespace Fantassin\Core\WordPress\Contracts;

interface BlockStyleInterface
{
    /**
     * @return array
     */
    public function getProperties(): array;

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * @return string
     */
    public function getStyleName(): string;

    /**
     * @return string[]
     */
    public function getBlocks(): array;
}
