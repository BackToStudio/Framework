<?php

namespace BackTo\Framework\Contracts;

interface AdminHooks extends HookInterface
{
    /**
     * Run admin hooks
     */
    public function hooks();
}
