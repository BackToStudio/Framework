<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Infrastructure;

use BackTo\Framework\Bundle\Security\Contracts\FileWriterInterface;

/**
 * Native PHP file writer.
 */
final class NativeFileWriter implements FileWriterInterface
{
    public function write(string $path, string $content): bool
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            return false;
        }

        return file_put_contents($path, $content) !== false;
    }

    public function exists(string $path): bool
    {
        return file_exists($path);
    }
}
