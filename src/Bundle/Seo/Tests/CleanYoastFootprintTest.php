<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Bundle\Seo\Actions\CleanYoastFootprint;
use PHPUnit\Framework\TestCase;

class CleanYoastFootprintTest extends TestCase
{
    public function testHooksRegistersYoastFilters(): void
    {
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $cleaner = new CleanYoastFootprint($hookDispatcher);

        $hookDispatcher->expects($this->exactly(2))
            ->method('addFilter')
            ->willReturnCallback(function (string $hook, string $callback) {
                static $call = 0;
                $call++;

                if ($call === 1) {
                    $this->assertSame('wpseo_debug_markers', $hook);
                    $this->assertSame('__return_false', $callback);
                } else {
                    $this->assertSame('wpseo_hide_version', $hook);
                    $this->assertSame('__return_true', $callback);
                }
            });

        $cleaner->hooks();
    }
}
