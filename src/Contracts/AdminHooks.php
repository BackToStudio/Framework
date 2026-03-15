<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

interface AdminHooks extends HookInterface
{
    /**
     * Run admin hooks
     */
    public function hooks(): void;
}
