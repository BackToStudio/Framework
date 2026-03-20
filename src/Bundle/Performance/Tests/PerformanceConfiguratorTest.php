<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;
use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;
use PHPUnit\Framework\TestCase;

class PerformanceConfiguratorTest extends TestCase
{
    public function testImplementsModuleConfiguratorInterface(): void
    {
        $configurator = new PerformanceConfigurator();

        $this->assertInstanceOf(ModuleConfiguratorInterface::class, $configurator);
    }

    public function testToParametersReturnsEmptyArrayByDefault(): void
    {
        $configurator = new PerformanceConfigurator();

        $this->assertSame([], $configurator->toParameters());
    }

    public function testFluentApiReturnsSelf(): void
    {
        $configurator = new PerformanceConfigurator();

        $this->assertSame($configurator, $configurator->cleanHead(true));
        $this->assertSame($configurator, $configurator->disableEmojis(true));
        $this->assertSame($configurator, $configurator->disableEmbeds(true));
        $this->assertSame($configurator, $configurator->disableXmlrpc(true));
        $this->assertSame($configurator, $configurator->heartbeatDisableFrontend(true));
        $this->assertSame($configurator, $configurator->heartbeatAdminInterval(60));
        $this->assertSame($configurator, $configurator->deferScripts(true));
        $this->assertSame($configurator, $configurator->deferExclude([]));
        $this->assertSame($configurator, $configurator->removeQueryStrings(true));
        $this->assertSame($configurator, $configurator->lazyLoadSkipFirst(1));
        $this->assertSame($configurator, $configurator->addDecodingAsync(true));
        $this->assertSame($configurator, $configurator->addFetchpriority(true));
        $this->assertSame($configurator, $configurator->minifyHtml(false));
        $this->assertSame($configurator, $configurator->preconnect([]));
        $this->assertSame($configurator, $configurator->dnsPrefetch([]));
        $this->assertSame($configurator, $configurator->preload([]));
        $this->assertSame($configurator, $configurator->revisionsLimit(5));
        $this->assertSame($configurator, $configurator->woocommerceOptimize(true));
        $this->assertSame($configurator, $configurator->pageCacheEnabled(false));
        $this->assertSame($configurator, $configurator->pageCacheTtl(3600));
        $this->assertSame($configurator, $configurator->dbCleanupRevisionsLimit(5));
        $this->assertSame($configurator, $configurator->cachePreloadEnabled(true));
        $this->assertSame($configurator, $configurator->cachePreloadDelay(5));
        $this->assertSame($configurator, $configurator->cachePreloadBatchSize(50));
        $this->assertSame($configurator, $configurator->htaccessGzip(true));
        $this->assertSame($configurator, $configurator->htaccessBrowserCache(true));
        $this->assertSame($configurator, $configurator->htaccessRemoveEtags(true));
        $this->assertSame($configurator, $configurator->htaccessKeepAlive(true));
        $this->assertSame($configurator, $configurator->htaccessStaticTtl(31536000));
    }

    public function testOnlyOverriddenValuesAreReturned(): void
    {
        $configurator = new PerformanceConfigurator();
        $configurator->pageCacheTtl(7200);

        $params = $configurator->toParameters();

        $this->assertCount(1, $params);
        $this->assertSame(7200, $params['performance.page_cache.ttl']);
    }

    public function testFullChainedConfiguration(): void
    {
        $configurator = (new PerformanceConfigurator())
            ->cleanHead(false)
            ->disableEmojis(false)
            ->minifyHtml(true)
            ->pageCacheEnabled(true)
            ->pageCacheTtl(7200)
            ->htaccessGzip(false)
            ->htaccessStaticTtl(86400)
            ->preconnect(['https://fonts.googleapis.com'])
            ->deferExclude(['jquery-core'])
            ->cachePreloadBatchSize(100);

        $params = $configurator->toParameters();

        $this->assertSame(false, $params['performance.clean_head']);
        $this->assertSame(false, $params['performance.disable_emojis']);
        $this->assertSame(true, $params['performance.minify_html']);
        $this->assertSame(true, $params['performance.page_cache.enabled']);
        $this->assertSame(7200, $params['performance.page_cache.ttl']);
        $this->assertSame(false, $params['performance.htaccess.gzip']);
        $this->assertSame(86400, $params['performance.htaccess.static_ttl']);
        $this->assertSame(['https://fonts.googleapis.com'], $params['performance.resource_hints.preconnect']);
        $this->assertSame(['jquery-core'], $params['performance.defer_exclude']);
        $this->assertSame(100, $params['performance.cache_preload.batch_size']);
    }

    public function testLastCallWins(): void
    {
        $configurator = (new PerformanceConfigurator())
            ->pageCacheTtl(3600)
            ->pageCacheTtl(7200);

        $this->assertSame(7200, $configurator->toParameters()['performance.page_cache.ttl']);
    }
}
