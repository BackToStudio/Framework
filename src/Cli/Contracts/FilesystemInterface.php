<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Contracts;

/**
 * Port interface for filesystem operations used by CLI commands.
 */
interface FilesystemInterface
{
    public function readFile(string $path): string|false;

    public function writeFile(string $path, string $content): bool;

    public function exists(string $path): bool;

    public function mkdir(string $path, int $permissions = 0755): bool;

    public function realpath(string $path): string|false;
}
