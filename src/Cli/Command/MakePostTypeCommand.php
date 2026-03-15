<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

/**
 * WP-CLI command: wp make:post-type <name> [--namespace=<namespace>] [--dir=<dir>] [--force]
 */
class MakePostTypeCommand extends AbstractMakeCommand
{
    protected function getTemplateName(): string
    {
        return 'PostType';
    }

    protected function getComponentType(): string
    {
        return 'post type';
    }
}
