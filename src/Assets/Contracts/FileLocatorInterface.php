<?php

namespace BackTo\Framework\Assets\Contracts;

/**
 * Port interface for locating files in the CMS media library.
 *
 * Abstracts WordPress get_attached_file() and wp_upload_dir(),
 * allowing the Assets layer to remain platform-agnostic.
 */
interface FileLocatorInterface
{
    /**
     * Get the filesystem path of an attachment by its ID.
     */
    public function getAttachedFile(int $attachmentId): string;

    /**
     * Get the upload directory information.
     *
     * @return array{basedir: string, baseurl: string}
     */
    public function getUploadDir(): array;
}
