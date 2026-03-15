<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets\Contracts;

use BackTo\Framework\Contracts\HookInterface;

/**
 * Represents a style asset to be enqueued.
 */
interface StyleInterface extends HookInterface
{
    public function getHandle(): string;

    public function getRelativePath(): string;

    public function getMedia(): string;
}
