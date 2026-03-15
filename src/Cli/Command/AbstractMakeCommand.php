<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

use BackTo\Framework\Cli\Generator\ClassGenerator;

/**
 * Base class for WP-CLI make commands.
 *
 * Usage: Register subclasses with WP_CLI::add_command() in your plugin/theme.
 */
abstract class AbstractMakeCommand
{
    protected ClassGenerator $generator;

    public function __construct()
    {
        $this->generator = new ClassGenerator();
    }

    abstract protected function getTemplateName(): string;

    abstract protected function getComponentType(): string;

    /**
     * @param array<string, string> $extraReplacements
     */
    protected function getTemplate(): string
    {
        $templatePath = \dirname(__DIR__) . '/Generator/Templates/' . $this->getTemplateName() . '.php.tpl';

        $content = \file_get_contents($templatePath);

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
            $this->error('Please provide a name for the ' . $this->getComponentType() . '.');
            return;
        }

        $replacements = $this->buildReplacements($name, $assocArgs);
        $template = $this->getTemplate();

        if ($template === '') {
            $this->error('Template not found for ' . $this->getComponentType() . '.');
            return;
        }

        $content = $this->generator->generate($template, $replacements);
        $outputDir = $assocArgs['dir'] ?? '.';
        $className = $replacements['{{className}}'];
        $filePath = \rtrim($outputDir, '/') . '/' . $className . '.php';

        if (\file_exists($filePath) && !isset($assocArgs['force'])) {
            $this->error("File {$filePath} already exists. Use --force to overwrite.");
            return;
        }

        if ($this->generator->writeFile($filePath, $content)) {
            $this->success("Created {$this->getComponentType()}: {$filePath}");
        } else {
            $this->error("Failed to write file: {$filePath}");
        }
    }

    protected function success(string $message): void
    {
        if (\class_exists('WP_CLI')) {
            \WP_CLI::success($message);
        }
    }

    protected function error(string $message): void
    {
        if (\class_exists('WP_CLI')) {
            \WP_CLI::error($message);
        }
    }
}
