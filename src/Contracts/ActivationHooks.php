<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

interface ActivationHooks extends HookInterface
{
    /**
     * Run activation hook
     */
    public function activate(): void;
}
