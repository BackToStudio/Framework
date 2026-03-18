<?php

declare(strict_types=1);

namespace BackTo\Framework\Theme\Tests;

require_once __DIR__ . '/wp_stubs.php';

use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Theme\Actions\RemoveSvgFilters;
use PHPUnit\Framework\TestCase;

class RemoveSvgFiltersTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['_removed_actions'] = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_removed_actions']);
    }

    public function testImplementsHooksInterface(): void
    {
        $this->assertInstanceOf(Hooks::class, new RemoveSvgFilters());
    }

    public function testRemovesExactly2Actions(): void
    {
        $action = new RemoveSvgFilters();
        $action->hooks();

        $this->assertCount(2, $GLOBALS['_removed_actions']);
    }

    public function testRemovesCoreAndGutenbergSvgFilters(): void
    {
        $action = new RemoveSvgFilters();
        $action->hooks();

        $callbacks = array_column($GLOBALS['_removed_actions'], 'callback');

        $this->assertContains('wp_global_styles_render_svg_filters', $callbacks);
        $this->assertContains('gutenberg_global_styles_render_svg_filters', $callbacks);
    }

    public function testBothTargetWpBodyOpen(): void
    {
        $action = new RemoveSvgFilters();
        $action->hooks();

        foreach ($GLOBALS['_removed_actions'] as $removed) {
            $this->assertSame('wp_body_open', $removed['tag']);
        }
    }
}
