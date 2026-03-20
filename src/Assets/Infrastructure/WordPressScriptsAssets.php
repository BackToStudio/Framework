<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets\Infrastructure;

use BackTo\Framework\Assets\AssetResolver;
use BackTo\Framework\Contracts\ScriptManagerInterface;

/**
 * WordPress adapter for registering/enqueuing scripts and styles.
 *
 * Delegates asset resolution to AssetResolver and WordPress calls to ScriptManagerInterface.
 */
final class WordPressScriptsAssets
{
    private readonly AssetResolver $assetResolver;
    private readonly ScriptManagerInterface $scriptManager;

    public function __construct(AssetResolver $assetResolver, ScriptManagerInterface $scriptManager)
    {
        $this->assetResolver = $assetResolver;
        $this->scriptManager = $scriptManager;
    }

    public function registerStyle(string $handle, string $relativePath, string $media = 'all'): void
    {
        $asset = $this->assetResolver->getAsset($relativePath);

        $this->scriptManager->registerStyle($handle, $asset['uri'], $asset['dependencies'], $asset['version'], $media);
    }

    public function enqueueStyle(string $handle, string $relativePath, string $media = 'all'): void
    {
        $this->registerStyle($handle, $relativePath, $media);
        $this->scriptManager->enqueueStyle($handle);
    }

    public function registerScript(string $handle, string $relativePath, bool $inFooter = true): void
    {
        $asset = $this->assetResolver->getAsset($relativePath);

        $this->scriptManager->registerScript($handle, $asset['uri'], $asset['dependencies'], $asset['version'], $inFooter);
    }

    public function enqueueScript(string $handle, string $relativePath, bool $inFooter = true): void
    {
        $this->registerScript($handle, $relativePath, $inFooter);
        $this->scriptManager->enqueueScript($handle);
    }

    public function getAssetResolver(): AssetResolver
    {
        return $this->assetResolver;
    }
}
