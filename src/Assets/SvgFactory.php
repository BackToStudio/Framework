<?php

namespace BackTo\Framework\Assets;


use function file_get_contents;
use function get_attached_file;
use function html_entity_decode;
use function str_contains;
use function str_replace;
use function wp_upload_dir;

class SvgFactory
{
    /**
     * @param string $image_id
     *
     * @return string
     */
    function getFromId(string $image_id): string
    {
        $path = get_attached_file($image_id);

        return $this->getFromPath($path);
    }

    /**
     * @param string $src
     *
     * @return string
     */
    function getFromSrc(string $src): string
    {
        $path = $src;

        $upload_dir = wp_upload_dir();
        if (str_contains($src, $upload_dir['baseurl'])) {
            $path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $src);
        }

        // Use path instead of url to prevent .htpasswd security.
        return $this->getFromPath($path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    function getFromPath(string $path): string
    {
        $res = file_get_contents($path);

        return html_entity_decode($res);
    }
}
