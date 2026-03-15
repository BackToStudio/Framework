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

        $routeNamespace = $assocArgs['route-namespace'] ?? 'app/v1';

        if (!\preg_match('/^[a-z0-9_-]+\/[a-z0-9_-]+$/', $routeNamespace)) {
            throw new \InvalidArgumentException('Invalid REST namespace: format must be namespace/version (e.g., app/v1).');
        }

        $replacements['{{routeNamespace}}'] = $routeNamespace;
        $replacements['{{route}}'] = '/' . \str_replace('_', '-', $replacements['{{key}}']);

        return $replacements;
    }
}
