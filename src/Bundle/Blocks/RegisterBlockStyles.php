<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Blocks;

use BackTo\Framework\Bundle\Blocks\Contracts\BlockStyleRegistrarInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

final class RegisterBlockStyles implements Hooks
{

    protected readonly BlockStyleRegistry $registry;

    private readonly HookDispatcherInterface $hookDispatcher;

    private readonly BlockStyleRegistrarInterface $registrar;

    public function __construct(
        BlockStyleRegistry $registry,
        BlockStyleRegistrarInterface $registrar,
        HookDispatcherInterface $hookDispatcher
    ) {
        $this->registry = $registry;
        $this->registrar = $registrar;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('after_setup_theme', [$this, 'registerCustomBlockStyles']);
    }

    public function registerCustomBlockStyles(): void
    {
        $blockStyles = $this->registry->getBlockStyles();

        foreach ($blockStyles as $blockStyle) {
            foreach ($blockStyle->getBlocks() as $blockName) {
                $this->registrar->register($blockName, $blockStyle->getProperties());
            }
        }
    }
}
