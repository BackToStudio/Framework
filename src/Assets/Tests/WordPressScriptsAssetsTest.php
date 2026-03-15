<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets\Tests;

use BackTo\Framework\Assets\WordPressScriptsAssets;
use BackTo\Framework\Exception\AssetBuildNotFoundException;
use PHPUnit\Framework\TestCase;

class WordPressScriptsAssetsTest extends TestCase
{
    public function testHasBuildFolderReturnsFalseWhenMissing(): void
    {
        $assets = new WordPressScriptsAssets('/nonexistent/path', 'https://example.com');

        $this->assertFalse($assets->hasBuildFolder());
    }

    public function testHasBuildFolderReturnsTrueWhenExists(): void
    {
        $tmpDir = \sys_get_temp_dir() . '/wp-assets-test-' . \uniqid();
        \mkdir($tmpDir . '/build', 0755, true);

        $assets = new WordPressScriptsAssets($tmpDir, 'https://example.com');

        $this->assertTrue($assets->hasBuildFolder());

        \rmdir($tmpDir . '/build');
        \rmdir($tmpDir);
    }

    public function testEnsureBuildFolderExistsThrowsWhenMissing(): void
    {
        $this->expectException(AssetBuildNotFoundException::class);

        $assets = new WordPressScriptsAssets('/nonexistent/path', 'https://example.com');
        $assets->ensureBuildFolderExists();
    }

    public function testGetAssetReturnsDefaultsWhenNoAssetFile(): void
    {
        $tmpDir = \sys_get_temp_dir() . '/wp-assets-test-' . \uniqid();
        \mkdir($tmpDir . '/build', 0755, true);

        $assets = new WordPressScriptsAssets($tmpDir, 'https://example.com');
        $result = $assets->getAsset('app.js');

        $this->assertSame([], $result['dependencies']);
        $this->assertNull($result['version']);
        $this->assertSame('https://example.com/build/app.js', $result['uri']);

        \rmdir($tmpDir . '/build');
        \rmdir($tmpDir);
    }

    public function testGetAssetReadsAssetPhpFile(): void
    {
        $tmpDir = \sys_get_temp_dir() . '/wp-assets-test-' . \uniqid();
        \mkdir($tmpDir . '/build', 0755, true);

        \file_put_contents(
            $tmpDir . '/build/app.asset.php',
            '<?php return ["dependencies" => ["wp-element"], "version" => "abc123"];',
        );

        $assets = new WordPressScriptsAssets($tmpDir, 'https://example.com');
        $result = $assets->getAsset('app.js');

        $this->assertSame(['wp-element'], $result['dependencies']);
        $this->assertSame('abc123', $result['version']);
        $this->assertSame('https://example.com/build/app.js', $result['uri']);

        \unlink($tmpDir . '/build/app.asset.php');
        \rmdir($tmpDir . '/build');
        \rmdir($tmpDir);
    }
}
