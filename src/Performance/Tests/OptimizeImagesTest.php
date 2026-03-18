<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Performance\Hooks\OptimizeImages;
use PHPUnit\Framework\TestCase;

class OptimizeImagesTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
    }

    public function testHooksRegistersAllFiltersByDefault(): void
    {
        $optimizer = new OptimizeImages($this->hookDispatcher);

        $this->hookDispatcher->expects($this->exactly(3))
            ->method('addFilter')
            ->willReturnCallback(function (string $hook) {
                $expected = [
                    'wp_get_attachment_image_attributes',
                    'wp_content_img_tag',
                    'wp_lazy_loading_enabled',
                ];

                $this->assertContains($hook, $expected, "Unexpected hook: $hook");
            });

        $optimizer->hooks();
    }

    public function testHooksSkipsDecodingFilterWhenDisabled(): void
    {
        $optimizer = new OptimizeImages($this->hookDispatcher, 1, false, true);

        $hooks = [];
        $this->hookDispatcher->expects($this->exactly(2))
            ->method('addFilter')
            ->willReturnCallback(function (string $hook) use (&$hooks) {
                $hooks[] = $hook;
            });

        $optimizer->hooks();

        $this->assertNotContains('wp_get_attachment_image_attributes', $hooks);
    }

    public function testHooksSkipsFetchPriorityFilterWhenDisabled(): void
    {
        $optimizer = new OptimizeImages($this->hookDispatcher, 1, true, false);

        $hooks = [];
        $this->hookDispatcher->expects($this->exactly(2))
            ->method('addFilter')
            ->willReturnCallback(function (string $hook) use (&$hooks) {
                $hooks[] = $hook;
            });

        $optimizer->hooks();

        $this->assertNotContains('wp_content_img_tag', $hooks);
    }

    public function testAddDecodingAsyncAddsAttribute(): void
    {
        $optimizer = new OptimizeImages($this->hookDispatcher);

        $attributes = ['src' => 'image.jpg', 'alt' => 'Test'];
        $result = $optimizer->addDecodingAsync($attributes);

        $this->assertSame('async', $result['decoding']);
    }

    public function testAddDecodingAsyncPreservesExistingValue(): void
    {
        $optimizer = new OptimizeImages($this->hookDispatcher);

        $attributes = ['src' => 'image.jpg', 'decoding' => 'sync'];
        $result = $optimizer->addDecodingAsync($attributes);

        $this->assertSame('sync', $result['decoding']);
    }

    public function testAddFetchPriorityToLcpAddsHighPriority(): void
    {
        $optimizer = new OptimizeImages($this->hookDispatcher, 1);

        $tag = '<img src="hero.jpg" alt="Hero">';
        $result = $optimizer->addFetchPriorityToLcp($tag, 'the_content', 1);

        $this->assertStringContainsString('fetchpriority="high"', $result);
        $this->assertStringNotContainsString('loading="lazy"', $result);
    }

    public function testEnableLazyLoadingReturnsTrue(): void
    {
        $optimizer = new OptimizeImages($this->hookDispatcher);

        $this->assertTrue($optimizer->enableLazyLoading());
    }
}
