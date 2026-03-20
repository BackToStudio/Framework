<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets\Tests;

use BackTo\Framework\Assets\AssetResolver;
use BackTo\Framework\Assets\Infrastructure\WordPressScriptsAssets;
use BackTo\Framework\Contracts\ScriptManagerInterface;
use BackTo\Framework\Exception\AssetBuildNotFoundException;
use PHPUnit\Framework\TestCase;

class WordPressScriptsAssetsTest extends TestCase
{
    private ScriptManagerInterface $scriptManager;

    protected function setUp(): void
    {
        $this->scriptManager = $this->createMock(ScriptManagerInterface::class);
    }

    public function testAssetResolverHasBuildFolderReturnsFalseWhenMissing(): void
    {
        $resolver = new AssetResolver('/nonexistent/path', 'https://example.com');

        $this->assertFalse($resolver->hasBuildFolder());
    }

    public function testAssetResolverHasBuildFolderReturnsTrueWhenExists(): void
    {
        $tmpDir = \sys_get_temp_dir() . '/wp-assets-test-' . \uniqid();
        \mkdir($tmpDir . '/build', 0755, true);

        $resolver = new AssetResolver($tmpDir, 'https://example.com');

        $this->assertTrue($resolver->hasBuildFolder());

        \rmdir($tmpDir . '/build');
        \rmdir($tmpDir);
    }

    public function testAssetResolverEnsureBuildFolderExistsThrowsWhenMissing(): void
    {
        $this->expectException(AssetBuildNotFoundException::class);

        $resolver = new AssetResolver('/nonexistent/path', 'https://example.com');
        $resolver->ensureBuildFolderExists();
    }

    public function testGetAssetReturnsDefaultsWhenNoAssetFile(): void
    {
        $tmpDir = \sys_get_temp_dir() . '/wp-assets-test-' . \uniqid();
        \mkdir($tmpDir . '/build', 0755, true);

        $resolver = new AssetResolver($tmpDir, 'https://example.com');
        $result = $resolver->getAsset('app.js');

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

        $resolver = new AssetResolver($tmpDir, 'https://example.com');
        $result = $resolver->getAsset('app.js');

        $this->assertSame(['wp-element'], $result['dependencies']);
        $this->assertSame('abc123', $result['version']);
        $this->assertSame('https://example.com/build/app.js', $result['uri']);

        \unlink($tmpDir . '/build/app.asset.php');
        \rmdir($tmpDir . '/build');
        \rmdir($tmpDir);
    }

    public function testRegisterStyleDelegatesToScriptManager(): void
    {
        $tmpDir = \sys_get_temp_dir() . '/wp-assets-test-' . \uniqid();
        \mkdir($tmpDir . '/build', 0755, true);

        $resolver = new AssetResolver($tmpDir, 'https://example.com');

        $this->scriptManager->expects($this->once())
            ->method('registerStyle')
            ->with('my-style', 'https://example.com/build/style.css', [], null, 'all');

        $assets = new WordPressScriptsAssets($resolver, $this->scriptManager);
        $assets->registerStyle('my-style', 'style.css');

        \rmdir($tmpDir . '/build');
        \rmdir($tmpDir);
    }

    public function testEnqueueScriptRegistersAndEnqueues(): void
    {
        $tmpDir = \sys_get_temp_dir() . '/wp-assets-test-' . \uniqid();
        \mkdir($tmpDir . '/build', 0755, true);

        $resolver = new AssetResolver($tmpDir, 'https://example.com');

        $this->scriptManager->expects($this->once())
            ->method('registerScript')
            ->with('my-script', 'https://example.com/build/app.js', [], null, true);

        $this->scriptManager->expects($this->once())
            ->method('enqueueScript')
            ->with('my-script');

        $assets = new WordPressScriptsAssets($resolver, $this->scriptManager);
        $assets->enqueueScript('my-script', 'app.js');

        \rmdir($tmpDir . '/build');
        \rmdir($tmpDir);
    }
}
