<?php

namespace Fantassin\Core\WordPress\Contracts;

interface Hooks extends HookInterface
{

    /**
     * Run front hooks
     */
    public function hooks();

}
