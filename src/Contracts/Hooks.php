<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

interface Hooks extends HookInterface
{

    /**
     * Run front hooks
     */
    public function hooks(): void;

}
