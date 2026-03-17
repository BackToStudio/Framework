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

        return $this->sanitizeSvg(html_entity_decode($res));
    }

    /**
     * Strip dangerous elements and attributes from SVG content.
     */
    private function sanitizeSvg(string $svg): string
    {
        // Remove script tags and their contents
        $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg) ?? $svg;

        // Remove dangerous elements (self-closing and open/close)
        $dangerousTags = ['foreignObject', 'set', 'animate', 'animateTransform', 'animateMotion', 'handler', 'listener'];
        foreach ($dangerousTags as $tag) {
            $svg = preg_replace('/<' . $tag . '\b[^>]*\/>/is', '', $svg) ?? $svg;
            $svg = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $svg) ?? $svg;
        }

        // Remove event handler attributes (onload, onclick, onerror, etc.)
        $svg = preg_replace('/\s+on\w+\s*=\s*"[^"]*"/i', '', $svg) ?? $svg;
        $svg = preg_replace("/\s+on\w+\s*=\s*'[^']*'/i", '', $svg) ?? $svg;

        // Remove javascript: and data: URIs in href attributes
        $svg = preg_replace('/href\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', 'href="removed"', $svg) ?? $svg;
        $svg = preg_replace('/href\s*=\s*["\']?\s*data:[^"\'>\s]*/i', 'href="removed"', $svg) ?? $svg;

        return $svg;
    }
}
