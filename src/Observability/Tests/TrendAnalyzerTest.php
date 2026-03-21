<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Observability\TrendAnalyzer;
use PHPUnit\Framework\TestCase;

final class TrendAnalyzerTest extends TestCase
{
    private MetricStoreInterface $metricStore;
    private AlertDispatcherInterface $alertDispatcher;
    private TrendAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->metricStore = $this->createMock(MetricStoreInterface::class);
        $this->alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $this->analyzer = new TrendAnalyzer($this->metricStore, $this->alertDispatcher);
    }

    public function test_detects_degrading_trend_higher_is_better(): void
    {
        $this->analyzer->watch('cache.hit_rate', 'higher_is_better', 20.0);

        $this->metricStore->method('summary')->willReturn([
            'cache.hit_rate' => ['current' => 60.0, 'min' => 55.0, 'max' => 65.0, 'avg' => 60.0, 'count' => 10],
        ]);

        $this->metricStore->method('history')->willReturn([
            ['value' => 90.0, 'type' => 'gauge', 'tags' => [], 'recorded_at' => \time() - 5000],
            ['value' => 95.0, 'type' => 'gauge', 'tags' => [], 'recorded_at' => \time() - 4000],
        ]);

        $this->alertDispatcher->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('degrading'));

        $results = $this->analyzer->analyze(3600);

        $this->assertArrayHasKey('cache.hit_rate', $results);
        $this->assertSame('degrading', $results['cache.hit_rate']['trend']);
    }

    public function test_detects_stable_trend(): void
    {
        $this->analyzer->watch('cache.hit_rate', 'higher_is_better', 20.0);

        $this->metricStore->method('summary')->willReturn([
            'cache.hit_rate' => ['current' => 92.0, 'min' => 90.0, 'max' => 95.0, 'avg' => 92.0, 'count' => 10],
        ]);

        $this->metricStore->method('history')->willReturn([
            ['value' => 90.0, 'type' => 'gauge', 'tags' => [], 'recorded_at' => \time() - 5000],
            ['value' => 93.0, 'type' => 'gauge', 'tags' => [], 'recorded_at' => \time() - 4000],
        ]);

        $this->alertDispatcher->expects($this->never())->method('warning');

        $results = $this->analyzer->analyze(3600);

        $this->assertSame('stable', $results['cache.hit_rate']['trend']);
    }

    public function test_detects_degrading_lower_is_better(): void
    {
        $this->analyzer->watch('db.total_time_ms', 'lower_is_better', 20.0);

        $this->metricStore->method('summary')->willReturn([
            'db.total_time_ms' => ['current' => 500.0, 'min' => 400.0, 'max' => 600.0, 'avg' => 500.0, 'count' => 10],
        ]);

        // Previous period: avg was much lower
        $this->metricStore->method('history')->willReturn([
            ['value' => 100.0, 'type' => 'gauge', 'tags' => [], 'recorded_at' => \time() - 5000],
            ['value' => 120.0, 'type' => 'gauge', 'tags' => [], 'recorded_at' => \time() - 4000],
        ]);

        $this->alertDispatcher->expects($this->once())->method('warning');

        $results = $this->analyzer->analyze(3600);

        $this->assertSame('degrading', $results['db.total_time_ms']['trend']);
    }

    public function test_skips_metrics_without_data(): void
    {
        $this->analyzer->watch('nonexistent', 'higher_is_better');

        $this->metricStore->method('summary')->willReturn([]);
        $this->alertDispatcher->expects($this->never())->method('warning');

        $results = $this->analyzer->analyze(3600);

        $this->assertEmpty($results);
    }

    public function test_watch_stores_config(): void
    {
        $this->analyzer->watch('cache.hit_rate', 'higher_is_better', 15.0);
        $this->analyzer->watch('db.time', 'lower_is_better', 25.0);

        $watched = $this->analyzer->getWatchedMetrics();

        $this->assertCount(2, $watched);
        $this->assertSame('higher_is_better', $watched['cache.hit_rate']['direction']);
        $this->assertSame(25.0, $watched['db.time']['threshold']);
    }
}
