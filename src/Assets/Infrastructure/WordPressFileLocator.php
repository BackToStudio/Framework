<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets\Infrastructure;

use BackTo\Framework\Assets\Contracts\FileLocatorInterface;

use function get_attached_file;
use function wp_upload_dir;

/**
 * WordPress adapter for file location (media library).
 */
final class WordPressFileLocator implements FileLocatorInterface
{
    public function getAttachedFile(int $attachmentId): string
    {
        $file = get_attached_file($attachmentId);

        return $file !== false ? $file : '';
    }

    /**
     * @return array{basedir: string, baseurl: string}
     */
    public function getUploadDir(): array
    {
        $dir = wp_upload_dir();

        return [
            'basedir' => $dir['basedir'],
            'baseurl' => $dir['baseurl'],
        ];
    }
}
