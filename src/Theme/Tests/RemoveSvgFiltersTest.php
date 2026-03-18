<?php

declare(strict_types=1);

namespace BackTo\Framework\Theme\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Theme\Actions\RemoveSvgFilters;
use PHPUnit\Framework\TestCase;

class RemoveSvgFiltersTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private array $removedActions;

    protected function setUp(): void
    {
        $this->removedActions = [];
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->hookDispatcher->method('removeAction')
            ->willReturnCallback(function (string $tag, $callback, int $priority = 10) {
                $this->removedActions[] = ['tag' => $tag, 'callback' => $callback, 'priority' => $priority];
            });
    }

    public function testImplementsHooksInterface(): void
    {
        $this->assertInstanceOf(Hooks::class, new RemoveSvgFilters($this->hookDispatcher));
    }

    public function testRemovesExactly2Actions(): void
    {
        $action = new RemoveSvgFilters($this->hookDispatcher);
        $action->hooks();

        $this->assertCount(2, $this->removedActions);
    }

    public function testRemovesCoreAndGutenbergSvgFilters(): void
    {
        $action = new RemoveSvgFilters($this->hookDispatcher);
        $action->hooks();

        $callbacks = array_column($this->removedActions, 'callback');

        $this->assertContains('wp_global_styles_render_svg_filters', $callbacks);
        $this->assertContains('gutenberg_global_styles_render_svg_filters', $callbacks);
    }

    public function testBothTargetWpBodyOpen(): void
    {
        $action = new RemoveSvgFilters($this->hookDispatcher);
        $action->hooks();

        foreach ($this->removedActions as $removed) {
            $this->assertSame('wp_body_open', $removed['tag']);
        }
    }
}
