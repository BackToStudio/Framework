<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Performance\Hooks\ServePageCache;
use PHPUnit\Framework\TestCase;

class ServePageCacheTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private PageCacheInterface $pageCache;
    private ServePageCache $cache;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->pageCache = $this->createMock(PageCacheInterface::class);
        $this->cache = new ServePageCache($this->hookDispatcher, $this->pageCache);
    }

    public function testHooksRegistersInitAndTemplateRedirect(): void
    {
        $this->hookDispatcher->expects($this->exactly(2))
            ->method('addAction')
            ->willReturnCallback(function (string $hook, $callback, int $priority = 10) {
                static $call = 0;
                $call++;

                if ($call === 1) {
                    $this->assertSame('init', $hook);
                    $this->assertSame(0, $priority);
                } else {
                    $this->assertSame('template_redirect', $hook);
                }
            });

        $this->cache->hooks();
    }

    public function testCaptureOutputStoresHtmlInCache(): void
    {
        // captureOutput calls getCurrentUrl() which needs is_ssl() and site_url()
        // We define them if not available for this test
        if (!function_exists('is_ssl')) {
            eval('function is_ssl() { return false; }');
        }
        if (!function_exists('site_url')) {
            eval('function site_url() { return "http://localhost"; }');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/test';

        $html = '<html><body>Hello World</body></html>';

        $this->pageCache->expects($this->once())
            ->method('put');

        $result = $this->cache->captureOutput($html);

        $this->assertStringContainsString($html, $result);
        $this->assertStringContainsString('X-Page-Cache: MISS', $result);
    }

    public function testCaptureOutputSkipsEmptyHtml(): void
    {
        $this->pageCache->expects($this->never())->method('put');

        $result = $this->cache->captureOutput('');

        $this->assertSame('', $result);
    }

    public function testCaptureOutputSkipsFatalError(): void
    {
        $html = '<br /><b>Fatal error</b>: Uncaught Error in file.php';

        $this->pageCache->expects($this->never())->method('put');

        $result = $this->cache->captureOutput($html);

        $this->assertSame($html, $result);
    }

    public function testConstructorWithCustomTtlAndPrefixes(): void
    {
        $cache = new ServePageCache(
            $this->hookDispatcher,
            $this->pageCache,
            7200,
            ['/custom-admin']
        );

        $this->assertInstanceOf(ServePageCache::class, $cache);
    }
}
