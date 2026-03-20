<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Http\Contracts\HttpClientInterface;
use BackTo\Framework\Http\Response;
use BackTo\Framework\Bundle\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Bundle\Performance\PreloadExecutor;
use PHPUnit\Framework\TestCase;

class PreloadExecutorTest extends TestCase
{
    private PageCacheInterface $pageCache;
    private HttpClientInterface $httpClient;
    private PreloadExecutor $executor;

    protected function setUp(): void
    {
        $this->pageCache = $this->createMock(PageCacheInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->executor = new PreloadExecutor($this->pageCache, $this->httpClient);
    }

    public function testPreloadSkipsAllCachedUrls(): void
    {
        $this->pageCache->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['https://example.com/', '<html>cached</html>'],
                ['https://example.com/about', '<html>about</html>'],
            ]);

        $this->httpClient->expects($this->never())->method('get');

        $this->executor->preload(['https://example.com/', 'https://example.com/about']);
    }

    public function testPreloadHandlesEmptyArray(): void
    {
        $this->pageCache->expects($this->never())->method('get');
        $this->httpClient->expects($this->never())->method('get');

        $this->executor->preload([]);
    }

    public function testPreloadSendsRequestForUncachedUrls(): void
    {
        $this->pageCache->method('get')->willReturn(null);

        $this->httpClient->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (string $url, array $args) {
                $this->assertArrayHasKey('timeout', $args);
                $this->assertFalse($args['blocking']);

                return new Response(200);
            });

        $this->executor->preload(['https://example.com/', 'https://example.com/about']);
    }
}
