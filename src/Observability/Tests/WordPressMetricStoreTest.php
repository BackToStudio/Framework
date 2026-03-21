<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Observability\Infrastructure\WordPressMetricStore;
use PHPUnit\Framework\TestCase;

/**
 * @requires function get_option
 */
final class WordPressMetricStoreTest extends TestCase
{
    private WordPressMetricStore $store;
    private array $storage = [];

    protected function setUp(): void
    {
        // These tests require WordPress function stubs
        if (!\function_exists('get_option')) {
            $this->markTestSkipped('WordPress functions not available');
        }

        $this->store = new WordPressMetricStore();
    }

    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(
            \BackTo\Framework\Observability\Contracts\MetricStoreInterface::class,
            $this->store,
        );
    }
}
