<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

/**
 * WP-CLI command: wp make:rest-route <name> [--namespace=<namespace>] [--dir=<dir>] [--route-namespace=<ns>] [--force]
 */
class MakeRestRouteCommand extends AbstractMakeCommand
{
    protected function getTemplateName(): string
    {
        return 'RestRoute';
    }

    protected function getComponentType(): string
    {
        return 'REST route';
    }

    /**
     * @param array<string, mixed> $assocArgs
     * @return array<string, string>
     */
    protected function buildReplacements(string $name, array $assocArgs): array
    {
        $replacements = parent::buildReplacements($name, $assocArgs);

        $replacements['{{routeNamespace}}'] = $assocArgs['route-namespace'] ?? 'app/v1';
        $replacements['{{route}}'] = '/' . \str_replace('_', '-', $replacements['{{key}}']);

        return $replacements;
    }
}
