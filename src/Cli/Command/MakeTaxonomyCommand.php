<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

/**
 * WP-CLI command: wp make:taxonomy <name> [--namespace=<namespace>] [--dir=<dir>] [--post-types=<types>] [--force]
 */
class MakeTaxonomyCommand extends AbstractMakeCommand
{
    protected function getTemplateName(): string
    {
        return 'Taxonomy';
    }

    protected function getComponentType(): string
    {
        return 'taxonomy';
    }

    /**
     * @param array<string, mixed> $assocArgs
     * @return array<string, string>
     */
    protected function buildReplacements(string $name, array $assocArgs): array
    {
        $replacements = parent::buildReplacements($name, $assocArgs);

        $postTypes = '';
        if (isset($assocArgs['post-types'])) {
            $types = \array_map('trim', \explode(',', (string) $assocArgs['post-types']));
            $postTypes = \implode(', ', \array_map(static fn (string $type): string => "'{$type}'", $types));
        }

        $replacements['{{postTypes}}'] = $postTypes;

        return $replacements;
    }
}
