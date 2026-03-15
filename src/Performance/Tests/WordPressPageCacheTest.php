<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Performance\Infrastructure\WordPressPageCache;
use PHPUnit\Framework\TestCase;

class WordPressPageCacheTest extends TestCase
{
    private WordPressPageCache $cache;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/btf_page_cache_test_' . uniqid();
        $this->cache = new WordPressPageCache($this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->cache->flush();
        if (is_dir($this->cacheDir)) {
            rmdir($this->cacheDir);
        }
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(PageCacheInterface::class, $this->cache);
    }

    public function testCreatesCacheDirectory(): void
    {
        $this->assertDirectoryExists($this->cacheDir);
    }

    public function testGetReturnsNullOnMiss(): void
    {
        $this->assertNull($this->cache->get('https://example.com/'));
    }

    public function testPutAndGet(): void
    {
        $url = 'https://example.com/page';
        $html = '<html><body>Hello</body></html>';

        $this->cache->put($url, $html, 3600);

        $cached = $this->cache->get($url);

        $this->assertNotNull($cached);
        $this->assertStringContainsString('Hello', $cached);
        $this->assertStringContainsString('Cached by BackTo Framework', $cached);
    }

    public function testExpiredCacheReturnsNull(): void
    {
        $url = 'https://example.com/expired';
        $html = '<html><body>Expired</body></html>';

        $this->cache->put($url, $html, -1);

        $this->assertNull($this->cache->get($url));
    }

    public function testInvalidateRemovesCache(): void
    {
        $url = 'https://example.com/page';
        $this->cache->put($url, '<html>content</html>', 3600);

        $this->assertNotNull($this->cache->get($url));

        $this->cache->invalidate($url);

        $this->assertNull($this->cache->get($url));
    }

    public function testFlushRemovesAllCaches(): void
    {
        $this->cache->put('https://example.com/a', '<html>A</html>', 3600);
        $this->cache->put('https://example.com/b', '<html>B</html>', 3600);

        $this->cache->flush();

        $this->assertNull($this->cache->get('https://example.com/a'));
        $this->assertNull($this->cache->get('https://example.com/b'));
    }

    public function testDifferentUrlsAreCachedSeparately(): void
    {
        $this->cache->put('https://example.com/a', '<html>A</html>', 3600);
        $this->cache->put('https://example.com/b', '<html>B</html>', 3600);

        $a = $this->cache->get('https://example.com/a');
        $b = $this->cache->get('https://example.com/b');

        $this->assertStringContainsString('A', $a);
        $this->assertStringContainsString('B', $b);
    }
}
