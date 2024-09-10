<?php

namespace BackTo\Framework\Assets;

use function file_exists;
use function wp_die;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_register_script;
use function wp_register_style;

class WordPressScriptsAssets
{
    private string $assetDirectory;
    private string $assetDirectoryUri;

    public function __construct(string $assetDirectory, string $assetDirectoryUri)
    {
        $this->assetDirectory    = $assetDirectory;
        $this->assetDirectoryUri = $assetDirectoryUri;
    }

    public function registerStyle(string $handle, string $relativePath, string $media = 'all')
    {
        $asset = $this->getAsset($relativePath);

        wp_register_style($handle, $asset['uri'], $asset['dependencies'], $asset['version'], $media);
    }

    public function enqueueStyle(string $handle, string $relativePath, string $media = 'all')
    {
        $asset = $this->getAsset($relativePath);

        wp_enqueue_style($handle, $asset['uri'], $asset['dependencies'], $asset['version'], $media);
    }

    public function registerScript(string $handle, string $relativePath, bool $inFooter = true)
    {
        $asset = $this->getAsset($relativePath);

        wp_register_script($handle, $asset['uri'], $asset['dependencies'], $asset['version'], $inFooter);
    }

    public function enqueueScript(string $handle, string $relativePath, bool $inFooter = true)
    {
        $asset = $this->getAsset($relativePath);

        wp_enqueue_script($handle, $asset['uri'], $asset['dependencies'], $asset['version'], $inFooter);
    }

    public function localizeScript(string $handle, string $objectName, array $data)
    {
        wp_localize_script($handle, $objectName, $data);
    }

    public function getAsset(string $relativePath)
    {
        $pathWithoutExtension = explode('.', $relativePath);
        array_pop($pathWithoutExtension);

        $assetPath = $this->getBuildFolderPath() . '/' . join('.', $pathWithoutExtension) . '.asset.php';

        $asset = file_exists($assetPath)
            ? require $assetPath
            : array(
                'dependencies' => [],
                'version'      => null,
            );

        $asset['uri'] = $this->getBuildFolderUri() . '/' . $relativePath;

        return $asset;
    }

    public function checkBuildFolderOrDieError(): void
    {
        if (! $this->hasBuildFolder()) {
            wp_die(
                'Make sure you already download JavaScript dependencies with <code>npm install</code> and run compilation with <code>npm run build</code>.'
            );
        }
    }

    public function hasBuildFolder(): bool
    {
        return file_exists($this->assetDirectory.'/build');
    }

    public function getBuildFolderPath(): string
    {
        $this->checkBuildFolderOrDieError();

        return $this->assetDirectory.'/build';
    }

    public function getBuildFolderUri(): string
    {
        $this->checkBuildFolderOrDieError();

        return $this->assetDirectoryUri.'/build';
    }
}
