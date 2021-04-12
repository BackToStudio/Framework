<?php

namespace Fantassin\Core\WordPress\Contracts;

interface AdminHooks extends HookInterface
{
    /**
     * Run admin hooks
     */
    public function hooks();
}
