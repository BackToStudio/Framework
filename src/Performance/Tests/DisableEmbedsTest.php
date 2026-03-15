<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Performance\Hooks\DisableEmbeds;
use PHPUnit\Framework\TestCase;

class DisableEmbedsTest extends TestCase
{
    public function testHooksAreRegistered(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))
            ->method('removeAction');
        $dispatcher->expects($this->once())
            ->method('addAction');
        $dispatcher->expects($this->exactly(2))
            ->method('addFilter');

        $hook = new DisableEmbeds($dispatcher);
        $hook->hooks();
    }

    public function testRemoveEmbedRewriteRules(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $hook = new DisableEmbeds($dispatcher);

        $rules = [
            'some-rule' => 'index.php?p=$matches[1]',
            'embed-rule' => 'index.php?p=$matches[1]&embed=true',
            'another-rule' => 'index.php?cat=$matches[1]',
        ];

        $result = $hook->removeEmbedRewriteRules($rules);

        $this->assertCount(2, $result);
        $this->assertArrayNotHasKey('embed-rule', $result);
        $this->assertArrayHasKey('some-rule', $result);
        $this->assertArrayHasKey('another-rule', $result);
    }
}
