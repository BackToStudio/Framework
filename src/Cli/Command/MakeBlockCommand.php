<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

/**
 * WP-CLI command: wp make:block <name> [--namespace=<namespace>] [--dir=<dir>] [--force]
 */
class MakeBlockCommand extends AbstractMakeCommand
{
    protected function getTemplateName(): string
    {
        return 'Block';
    }

    protected function getComponentType(): string
    {
        return 'block';
    }

    /**
     * @param array<string, mixed> $assocArgs
     * @return array<string, string>
     */
    protected function buildReplacements(string $name, array $assocArgs): array
    {
        $replacements = parent::buildReplacements($name, $assocArgs);

        $namespace = $assocArgs['block-namespace'] ?? 'custom';
        $replacements['{{name}}'] = $namespace . '/' . $replacements['{{key}}'];

        return $replacements;
    }
}
