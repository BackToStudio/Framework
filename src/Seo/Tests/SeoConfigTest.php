<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\SeoConfig;
use PHPUnit\Framework\TestCase;

class SeoConfigTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/seo-config-test-' . uniqid();
        mkdir($this->tmpDir . '/config', 0755, true);
    }

    protected function tearDown(): void
    {
        $configFile = $this->tmpDir . '/config/seo.php';

        if (file_exists($configFile)) {
            unlink($configFile);
        }

        if (is_dir($this->tmpDir . '/config')) {
            rmdir($this->tmpDir . '/config');
        }

        if (is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
    }

    public function testReturnsEmptyConfigWhenFileDoesNotExist(): void
    {
        $config = new SeoConfig($this->tmpDir);

        $this->assertSame([], $config->all());
        $this->assertSame([], $config->getPostTypeMap());
        $this->assertTrue($config->shouldDisablePluginSchema());
    }

    public function testLoadsConfigFromFile(): void
    {
        $this->writeConfig([
            'schema' => [
                'post_type_map' => [
                    'post' => 'App\\Seo\\ArticleGen',
                    'formation' => 'App\\Seo\\CourseGen',
                ],
                'disable_plugin_schema' => false,
            ],
        ]);

        $config = new SeoConfig($this->tmpDir);

        $this->assertSame([
            'post' => 'App\\Seo\\ArticleGen',
            'formation' => 'App\\Seo\\CourseGen',
        ], $config->getPostTypeMap());

        $this->assertFalse($config->shouldDisablePluginSchema());
    }

    public function testGetNestedValue(): void
    {
        $this->writeConfig([
            'schema' => [
                'post_type_map' => ['post' => 'ArticleGen'],
            ],
        ]);

        $config = new SeoConfig($this->tmpDir);

        $this->assertSame(['post' => 'ArticleGen'], $config->get('schema.post_type_map'));
        $this->assertSame('ArticleGen', $config->get('schema.post_type_map.post'));
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $config = new SeoConfig($this->tmpDir);

        $this->assertNull($config->get('nonexistent'));
        $this->assertSame('fallback', $config->get('nonexistent', 'fallback'));
        $this->assertSame('default', $config->get('schema.deep.missing', 'default'));
    }

    public function testDisablePluginSchemaDefaultsToTrue(): void
    {
        $this->writeConfig(['schema' => []]);

        $config = new SeoConfig($this->tmpDir);
        $this->assertTrue($config->shouldDisablePluginSchema());
    }

    public function testHandlesNonArrayReturn(): void
    {
        file_put_contents(
            $this->tmpDir . '/config/seo.php',
            '<?php return "not an array";'
        );

        $config = new SeoConfig($this->tmpDir);
        $this->assertSame([], $config->all());
    }

    public function testPostTypeMapDefaultsToEmptyArray(): void
    {
        $this->writeConfig([]);

        $config = new SeoConfig($this->tmpDir);
        $this->assertSame([], $config->getPostTypeMap());
    }

    private function writeConfig(array $data): void
    {
        $export = var_export($data, true);
        file_put_contents(
            $this->tmpDir . '/config/seo.php',
            "<?php\n\nreturn {$export};\n"
        );
    }
}
