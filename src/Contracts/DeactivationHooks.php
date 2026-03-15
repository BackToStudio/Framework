<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

interface DeactivationHooks extends HookInterface
{
    /**
     * Run activation hook
     */
    public function deactivate();
}
