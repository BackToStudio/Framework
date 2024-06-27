<?php

namespace BackTo\Framework\Contracts;

interface ActivationHooks extends HookInterface
{
    /**
     * Run activation hook
     */
    public function activate();
}
