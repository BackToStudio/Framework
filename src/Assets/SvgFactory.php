<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets;

use BackTo\Framework\Assets\Contracts\FileLocatorInterface;

use function file_get_contents;
use function html_entity_decode;
use function str_contains;
use function str_replace;

class SvgFactory
{
    private FileLocatorInterface $fileLocator;

    public function __construct(FileLocatorInterface $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * @param int $image_id
     *
     * @return string
     */
    public function getFromId(int $image_id): string
    {
        $path = $this->fileLocator->getAttachedFile($image_id);

        return $this->getFromPath($path);
    }

    /**
     * @param string $src
     *
     * @return string
     */
    public function getFromSrc(string $src): string
    {
        $path = $src;

        $uploadDir = $this->fileLocator->getUploadDir();
        if (str_contains($src, $uploadDir['baseurl'])) {
            $path = str_replace($uploadDir['baseurl'], $uploadDir['basedir'], $src);
        }

        // Use path instead of url to prevent .htpasswd security.
        return $this->getFromPath($path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getFromPath(string $path): string
    {
        $res = file_get_contents($path);

        if ($res === false) {
            return '';
        }

        return html_entity_decode($res);
    }
}
