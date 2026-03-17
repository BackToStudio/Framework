<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

/**
 * WP-CLI command: wp make:taxonomy <name> [--namespace=<namespace>] [--dir=<dir>] [--post-types=<types>] [--force]
 */
final class MakeTaxonomyCommand extends AbstractMakeCommand
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
            $types = \array_filter($types, static fn (string $type): bool => \preg_match('/^[a-z0-9_-]{1,20}$/', $type) === 1);
            $postTypes = \implode(', ', \array_map(static fn (string $type): string => "'{$type}'", $types));
        }

        $replacements['{{postTypes}}'] = $postTypes;

        return $replacements;
    }
}
