<?php

namespace Fantassin\Core\WordPress\Contracts;

interface ActivationHooks extends HookInterface
{
    /**
     * Run activation hook
     */
    public function activate();
}
