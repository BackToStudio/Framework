<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Reads file content for security validation purposes.
 *
 * Abstracts file I/O so that security rules can be tested
 * without touching the filesystem.
 */
interface FileContentReaderInterface
{
    /**
     * Read the first N bytes of a file (for magic byte validation).
     */
    public function readBytes(string $filePath, int $length): ?string;

    /**
     * Read the full content of a file.
     */
    public function readAll(string $filePath): ?string;

    /**
     * Get the file size in bytes, or null if unavailable.
     */
    public function fileSize(string $filePath): ?int;
}
