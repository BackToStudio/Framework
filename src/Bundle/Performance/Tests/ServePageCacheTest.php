<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\QueryContextInterface;
use BackTo\Framework\Bundle\Performance\CacheableRequestChecker;
use BackTo\Framework\Bundle\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Bundle\Performance\Hooks\ServePageCache;
use BackTo\Framework\Bundle\Performance\RequestUrlResolver;
use PHPUnit\Framework\TestCase;

class ServePageCacheTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private PageCacheInterface $pageCache;
    private CacheableRequestChecker $requestChecker;
    private RequestUrlResolver $urlResolver;
    private QueryContextInterface $queryContext;
    private ServePageCache $cache;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->pageCache = $this->createMock(PageCacheInterface::class);
        $this->requestChecker = $this->createMock(CacheableRequestChecker::class);
        $this->urlResolver = $this->createMock(RequestUrlResolver::class);
        $this->queryContext = $this->createMock(QueryContextInterface::class);
        $this->cache = new ServePageCache(
            $this->hookDispatcher,
            $this->pageCache,
            $this->requestChecker,
            $this->urlResolver,
            $this->queryContext,
        );
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
        $this->urlResolver->method('getCurrentUrl')->willReturn('http://localhost/test');

        $html = '<html><body>Hello World</body></html>';

        $this->pageCache->expects($this->once())
            ->method('put')
            ->with('http://localhost/test', $html, 3600);

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
}
