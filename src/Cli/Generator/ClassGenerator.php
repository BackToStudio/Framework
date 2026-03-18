<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Generator;

use BackTo\Framework\Cli\Contracts\FilesystemInterface;

/**
 * Generates PHP class files from templates.
 *
 * Used by WP-CLI commands to scaffold framework components.
 */
final class ClassGenerator
{
    private readonly FilesystemInterface $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param array<string, string> $replacements
     */
    public function generate(string $template, array $replacements): string
    {
        return \str_replace(
            \array_keys($replacements),
            \array_values($replacements),
            $template,
        );
    }

    public function writeFile(string $path, string $content): bool
    {
        return $this->filesystem->writeFile($path, $content);
    }

    /**
     * Convert a key like "event" to a class name like "Event".
     */
    public function toClassName(string $key): string
    {
        return \str_replace(['-', '_', ' '], '', \ucwords($key, '-_ '));
    }

    /**
     * Convert a class name like "EventCategory" to a key like "event_category".
     */
    public function toKey(string $className): string
    {
        $snake = \preg_replace('/[A-Z]/', '_$0', \lcfirst($className));

        return \strtolower($snake ?? $className);
    }
}
