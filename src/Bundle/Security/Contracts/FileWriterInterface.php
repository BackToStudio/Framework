<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Writes files to the filesystem.
 *
 * Abstracts file write operations so that security rules
 * can be tested without touching the filesystem.
 */
interface FileWriterInterface
{
    /**
     * Write content to a file path.
     *
     * @return bool True if the file was written successfully.
     */
    public function write(string $path, string $content): bool;

    /**
     * Whether a file already exists.
     */
    public function exists(string $path): bool;
}
