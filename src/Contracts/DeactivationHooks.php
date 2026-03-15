<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

interface DeactivationHooks extends HookInterface
{
    /**
     * Run deactivation hook
     */
    public function deactivate(): void;
}
