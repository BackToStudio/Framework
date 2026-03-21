<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Infrastructure;

use BackTo\Framework\Bundle\Security\Contracts\FileContentReaderInterface;

/**
 * Native PHP file content reader.
 */
final class NativeFileContentReader implements FileContentReaderInterface
{
    public function readBytes(string $filePath, int $length): ?string
    {
        $handle = @fopen($filePath, 'rb');

        if ($handle === false) {
            return null;
        }

        $bytes = fread($handle, max(1, $length));
        fclose($handle);

        return $bytes !== false ? $bytes : null;
    }

    public function readAll(string $filePath): ?string
    {
        $content = @file_get_contents($filePath);

        return $content !== false ? $content : null;
    }

    public function fileSize(string $filePath): ?int
    {
        $size = @filesize($filePath);

        return $size !== false ? $size : null;
    }
}
