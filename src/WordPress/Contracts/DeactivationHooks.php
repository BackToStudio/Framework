<?php

namespace Fantassin\Core\WordPress\Contracts;

interface DeactivationHooks extends HookInterface
{
    /**
     * Run activation hook
     */
    public function deactivate();
}
