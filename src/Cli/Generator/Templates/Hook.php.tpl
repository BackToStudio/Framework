<?php

declare(strict_types=1);

namespace {{namespace}};

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

class {{className}} implements Hooks
{
    private HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'execute']);
    }

    public function execute(): void
    {
        // TODO: implement hook logic
    }
}
