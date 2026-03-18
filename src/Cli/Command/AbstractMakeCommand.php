<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

use BackTo\Framework\Cli\Contracts\CliOutputInterface;
use BackTo\Framework\Cli\Contracts\FilesystemInterface;
use BackTo\Framework\Cli\Generator\ClassGenerator;

/**
 * Base class for WP-CLI make commands.
 *
 * Usage: Register subclasses with WP_CLI::add_command() in your plugin/theme.
 */
abstract class AbstractMakeCommand
{
    protected readonly ClassGenerator $generator;
    protected readonly CliOutputInterface $output;
    protected readonly FilesystemInterface $filesystem;

    public function __construct(
        FilesystemInterface $filesystem,
        CliOutputInterface $output,
    ) {
        $this->filesystem = $filesystem;
        $this->output = $output;
        $this->generator = new ClassGenerator($filesystem);
    }

    abstract protected function getTemplateName(): string;

    abstract protected function getComponentType(): string;

    protected function getTemplate(): string
    {
        $templatePath = \dirname(__DIR__) . '/Generator/Templates/' . $this->getTemplateName() . '.php.tpl';

        $content = $this->filesystem->readFile($templatePath);

        if ($content === false) {
            return '';
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $assocArgs
     * @return array<string, string>
     */
    protected function buildReplacements(string $name, array $assocArgs): array
    {
        $className = $this->generator->toClassName($name);
        $key = $this->generator->toKey($className);
        $namespace = $assocArgs['namespace'] ?? 'App';

        if (!\preg_match('/^[A-Za-z][A-Za-z0-9\\\\]*$/', $namespace)) {
            throw new \InvalidArgumentException('Invalid namespace: must be a valid PHP namespace.');
        }

        return [
            '{{namespace}}' => $namespace,
            '{{className}}' => $className,
            '{{key}}' => $key,
            '{{label}}' => \ucfirst(\str_replace('_', ' ', $key)),
        ];
    }

    /**
     * @param string[] $args
     * @param array<string, mixed> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $name = $args[0] ?? '';

        if ($name === '') {
            $this->output->error('Please provide a name for the ' . $this->getComponentType() . '.');
            return;
        }

        if (!\preg_match('/^[A-Za-z][A-Za-z0-9_\- ]*$/', $name)) {
            $this->output->error('Invalid name: use only letters, numbers, hyphens, underscores, and spaces.');
            return;
        }

        $replacements = $this->buildReplacements($name, $assocArgs);
        $template = $this->getTemplate();

        if ($template === '') {
            $this->output->error('Template not found for ' . $this->getComponentType() . '.');
            return;
        }

        $content = $this->generator->generate($template, $replacements);
        $outputDir = $assocArgs['dir'] ?? '.';
        $className = $replacements['{{className}}'];

        $realOutputDir = $this->filesystem->realpath($outputDir);
        if ($realOutputDir === false) {
            $this->output->error("Output directory does not exist: {$outputDir}");
            return;
        }

        $filePath = $realOutputDir . '/' . $className . '.php';

        if ($this->filesystem->exists($filePath) && !isset($assocArgs['force'])) {
            $this->output->error("File {$filePath} already exists. Use --force to overwrite.");
            return;
        }

        if ($this->generator->writeFile($filePath, $content)) {
            $this->output->success("Created {$this->getComponentType()}: {$filePath}");
        } else {
            $this->output->error("Failed to write file: {$filePath}");
        }
    }
}
