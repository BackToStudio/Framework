<?php

declare(strict_types=1);

namespace BackTo\Framework\Blocks;

use BackTo\Framework\Blocks\Contracts\BlockRegistrarInterface;
use BackTo\Framework\Contracts\DynamicBlock;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

class RegisterBlock implements Hooks
{
    private BlockRegistry $registry;
    private BlockRegistrarInterface $registrar;
    private HookDispatcherInterface $hookDispatcher;

    public function __construct(
        BlockRegistry $registry,
        BlockRegistrarInterface $registrar,
        HookDispatcherInterface $hookDispatcher
    ) {
        $this->registry = $registry;
        $this->registrar = $registrar;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'registerCustomBlocks']);
    }

    public function registerCustomBlocks(): void
    {
        foreach ($this->registry->getBlocks() as $block) {
            if ($this->registrar->exists($block->getName())) {
                continue;
            }

            $args = [];

            if ($block instanceof DynamicBlock) {
                $args['render_callback'] = [$block, 'renderBlock'];
            }

            $this->registrar->register($block->getName(), $args);
        }
    }
}
