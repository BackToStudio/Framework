<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets;

use BackTo\Framework\Exception\AssetBuildNotFoundException;

/**
 * Resolves asset metadata (dependencies, version, URI) from the build folder.
 *
 * Pure domain logic — no WordPress dependency.
 */
class AssetResolver
{
    private readonly string $assetDirectory;
    private readonly string $assetDirectoryUri;

    public function __construct(string $assetDirectory, string $assetDirectoryUri)
    {
        $this->assetDirectory = $assetDirectory;
        $this->assetDirectoryUri = $assetDirectoryUri;
    }

    /**
     * @return array{dependencies: string[], version: string|null, uri: string}
     *
     * @throws AssetBuildNotFoundException
     */
    public function getAsset(string $relativePath): array
    {
        if (str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
            throw new \InvalidArgumentException('Invalid asset path: directory traversal is not allowed.');
        }

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
