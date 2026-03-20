<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\ScriptManagerInterface;
use BackTo\Framework\Bundle\Performance\Hooks\Cleanup\DisableEmbeds;
use PHPUnit\Framework\TestCase;

class DisableEmbedsTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private ScriptManagerInterface $scriptManager;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->scriptManager = $this->createMock(ScriptManagerInterface::class);
    }

    public function testHooksAreRegistered(): void
    {
        $this->hookDispatcher->expects($this->exactly(2))
            ->method('removeAction');
        $this->hookDispatcher->expects($this->once())
            ->method('addAction');
        $this->hookDispatcher->expects($this->exactly(2))
            ->method('addFilter');

        $hook = new DisableEmbeds($this->hookDispatcher, $this->scriptManager);
        $hook->hooks();
    }

    public function testDeregisterEmbedScriptCallsScriptManager(): void
    {
        $this->scriptManager->expects($this->once())
            ->method('deregisterScript')
            ->with('wp-embed');

        $hook = new DisableEmbeds($this->hookDispatcher, $this->scriptManager);
        $hook->deregisterEmbedScript();
    }

    public function testRemoveEmbedRewriteRules(): void
    {
        $hook = new DisableEmbeds($this->hookDispatcher, $this->scriptManager);

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
