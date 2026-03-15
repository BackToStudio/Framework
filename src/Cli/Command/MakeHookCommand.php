<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

/**
 * WP-CLI command: wp make:hook <name> [--namespace=<namespace>] [--dir=<dir>] [--force]
 */
class MakeHookCommand extends AbstractMakeCommand
{
    protected function getTemplateName(): string
    {
        return 'Hook';
    }

    protected function getComponentType(): string
    {
        return 'hook';
    }
}
