<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Tests;

use BackTo\Framework\Cache\CacheMetricsDecorator;
use BackTo\Framework\Cache\Contracts\CacheInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use PHPUnit\Framework\TestCase;

final class CacheMetricsDecoratorTest extends TestCase
{
    private CacheInterface $inner;
    private MetricStoreInterface $metricStore;
    private CacheMetricsDecorator $decorator;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(CacheInterface::class);
        $this->metricStore = $this->createMock(MetricStoreInterface::class);
        $this->decorator = new CacheMetricsDecorator($this->inner, $this->metricStore);
    }

    public function test_records_hit_on_cache_hit(): void
    {
        $this->inner->method('get')->willReturn('cached_value');

        $this->metricStore->expects($this->once())
            ->method('increment')
            ->with('cache.hits');

        $result = $this->decorator->get('key');

        $this->assertSame('cached_value', $result);
        $this->assertSame(1, $this->decorator->getHits());
        $this->assertSame(0, $this->decorator->getMisses());
    }

    public function test_records_miss_on_cache_miss(): void
    {
        $this->inner->method('get')->willReturn(null);

        $this->metricStore->expects($this->once())
            ->method('increment')
            ->with('cache.misses');

        $result = $this->decorator->get('key');

        $this->assertNull($result);
        $this->assertSame(0, $this->decorator->getHits());
        $this->assertSame(1, $this->decorator->getMisses());
    }

    public function test_delegates_set_to_inner(): void
    {
        $this->inner->expects($this->once())
            ->method('set')
            ->with('key', 'value', 60)
            ->willReturn(true);

        $this->assertTrue($this->decorator->set('key', 'value', 60));
    }

    public function test_delegates_delete_to_inner(): void
    {
        $this->inner->expects($this->once())
            ->method('delete')
            ->with('key')
            ->willReturn(true);

        $this->assertTrue($this->decorator->delete('key'));
    }

    public function test_delegates_has_to_inner(): void
    {
        $this->inner->expects($this->once())
            ->method('has')
            ->with('key')
            ->willReturn(true);

        $this->assertTrue($this->decorator->has('key'));
    }

    public function test_flush_metrics_records_hit_rate(): void
    {
        // Simulate 3 hits and 1 miss
        $this->inner->method('get')
            ->willReturnOnConsecutiveCalls('a', 'b', 'c', null);

        $this->decorator->get('k1');
        $this->decorator->get('k2');
        $this->decorator->get('k3');
        $this->decorator->get('k4');

        $this->metricStore->expects($this->exactly(2))
            ->method('record');

        $this->decorator->flushMetrics();
    }

    public function test_flush_metrics_skips_when_no_requests(): void
    {
        $this->metricStore->expects($this->never())
            ->method('record');

        $this->decorator->flushMetrics();
    }
}
