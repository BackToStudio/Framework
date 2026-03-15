<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets;

use BackTo\Framework\Exception\AssetBuildNotFoundException;

use function file_exists;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_localize_script;
use function wp_register_script;
use function wp_register_style;

class WordPressScriptsAssets
{
    private string $assetDirectory;
    private string $assetDirectoryUri;

    public function __construct(string $assetDirectory, string $assetDirectoryUri)
    {
        $this->assetDirectory = $assetDirectory;
        $this->assetDirectoryUri = $assetDirectoryUri;
    }

    public function registerStyle(string $handle, string $relativePath, string $media = 'all'): void
    {
        $asset = $this->getAsset($relativePath);

        wp_register_style($handle, $asset['uri'], $asset['dependencies'], $asset['version'], $media);
    }

    public function enqueueStyle(string $handle, string $relativePath, string $media = 'all'): void
    {
        $asset = $this->getAsset($relativePath);

        wp_enqueue_style($handle, $asset['uri'], $asset['dependencies'], $asset['version'], $media);
    }

    public function registerScript(string $handle, string $relativePath, bool $inFooter = true): void
    {
        $asset = $this->getAsset($relativePath);

        wp_register_script($handle, $asset['uri'], $asset['dependencies'], $asset['version'], $inFooter);
    }

    public function enqueueScript(string $handle, string $relativePath, bool $inFooter = true): void
    {
        $asset = $this->getAsset($relativePath);

        wp_enqueue_script($handle, $asset['uri'], $asset['dependencies'], $asset['version'], $inFooter);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function localizeScript(string $handle, string $objectName, array $data): void
    {
        wp_localize_script($handle, $objectName, $data);
    }

    /**
     * @return array{dependencies: string[], version: string|null, uri: string}
     *
     * @throws AssetBuildNotFoundException
     */
    public function getAsset(string $relativePath): array
    {
        $pathWithoutExtension = explode('.', $relativePath);
        array_pop($pathWithoutExtension);

        $assetPath = $this->getBuildFolderPath() . '/' . implode('.', $pathWithoutExtension) . '.asset.php';

        $asset = file_exists($assetPath)
            ? require $assetPath
            : [
                'dependencies' => [],
                'version' => null,
            ];

        $asset['uri'] = $this->getBuildFolderUri() . '/' . $relativePath;

        return $asset;
    }

    /**
     * @throws AssetBuildNotFoundException
     */
    public function ensureBuildFolderExists(): void
    {
        if (!$this->hasBuildFolder()) {
            throw AssetBuildNotFoundException::forDirectory($this->assetDirectory);
        }
    }

    public function hasBuildFolder(): bool
    {
        return file_exists($this->assetDirectory . '/build');
    }

    /**
     * @throws AssetBuildNotFoundException
     */
    public function getBuildFolderPath(): string
    {
        $this->ensureBuildFolderExists();

        return $this->assetDirectory . '/build';
    }

    /**
     * @throws AssetBuildNotFoundException
     */
    public function getBuildFolderUri(): string
    {
        $this->ensureBuildFolderExists();

        return $this->assetDirectoryUri . '/build';
    }
}
