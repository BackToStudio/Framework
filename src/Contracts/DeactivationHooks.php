<?php

namespace BackTo\Framework\Contracts;

interface DeactivationHooks extends HookInterface
{
    /**
     * Run activation hook
     */
    public function deactivate();
}
