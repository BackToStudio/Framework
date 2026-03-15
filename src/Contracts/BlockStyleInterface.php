<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

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
