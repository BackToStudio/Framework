<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Infrastructure;

use BackTo\Framework\Cli\Contracts\FilesystemInterface;

/**
 * Native PHP filesystem adapter.
 */
final class NativeFilesystem implements FilesystemInterface
{
    public function readFile(string $path): string|false
    {
        return \file_get_contents($path);
    }

    public function writeFile(string $path, string $content): bool
    {
        $dir = \dirname($path);

        if (!\is_dir($dir)) {
            \mkdir($dir, 0755, true);
        }

        return \file_put_contents($path, $content) !== false;
    }

    public function exists(string $path): bool
    {
        return \file_exists($path);
    }

    public function mkdir(string $path, int $permissions = 0755): bool
    {
        return \mkdir($path, $permissions, true);
    }

    public function realpath(string $path): string|false
    {
        return \realpath($path);
    }
}
