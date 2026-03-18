<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Performance\PreloadExecutor;
use PHPUnit\Framework\TestCase;

class PreloadExecutorTest extends TestCase
{
    private PageCacheInterface $pageCache;
    private PreloadExecutor $executor;

    protected function setUp(): void
    {
        $this->pageCache = $this->createMock(PageCacheInterface::class);
        $this->executor = new PreloadExecutor($this->pageCache);
    }

    public function testPreloadSkipsAllCachedUrls(): void
    {
        // When all URLs are cached, wp_remote_get is never called
        $this->pageCache->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['https://example.com/', '<html>cached</html>'],
                ['https://example.com/about', '<html>about</html>'],
            ]);

        $this->executor->preload(['https://example.com/', 'https://example.com/about']);
    }

    public function testPreloadHandlesEmptyArray(): void
    {
        $this->pageCache->expects($this->never())->method('get');

        $this->executor->preload([]);
    }
}
