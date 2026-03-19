<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\ScriptManagerInterface;
use BackTo\Framework\Performance\Hooks\DisableHeartbeat;
use PHPUnit\Framework\TestCase;

class DisableHeartbeatTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private ScriptManagerInterface $scriptManager;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->scriptManager = $this->createMock(ScriptManagerInterface::class);
    }

    public function testHooksRegistersDeregisterAndFilterByDefault(): void
    {
        $heartbeat = new DisableHeartbeat($this->hookDispatcher, $this->scriptManager);

        $this->hookDispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', [$heartbeat, 'deregisterHeartbeatOnFrontend']);

        $this->hookDispatcher->expects($this->once())
            ->method('addFilter')
            ->with('heartbeat_settings', [$heartbeat, 'setAdminInterval']);

        $heartbeat->hooks();
    }

    public function testHooksSkipsDeregisterWhenFrontendDisableIsFalse(): void
    {
        $heartbeat = new DisableHeartbeat($this->hookDispatcher, $this->scriptManager, false);

        $this->hookDispatcher->expects($this->never())
            ->method('addAction');

        $this->hookDispatcher->expects($this->once())
            ->method('addFilter');

        $heartbeat->hooks();
    }

    public function testDeregisterHeartbeatOnFrontendCallsScriptManager(): void
    {
        $this->hookDispatcher->method('isAdmin')->willReturn(false);
        $this->scriptManager->expects($this->once())
            ->method('deregisterScript')
            ->with('heartbeat');

        $heartbeat = new DisableHeartbeat($this->hookDispatcher, $this->scriptManager);
        $heartbeat->deregisterHeartbeatOnFrontend();
    }

    public function testDeregisterHeartbeatSkipsOnAdmin(): void
    {
        $this->hookDispatcher->method('isAdmin')->willReturn(true);
        $this->scriptManager->expects($this->never())->method('deregisterScript');

        $heartbeat = new DisableHeartbeat($this->hookDispatcher, $this->scriptManager);
        $heartbeat->deregisterHeartbeatOnFrontend();
    }

    public function testSetAdminIntervalSetsCustomInterval(): void
    {
        $heartbeat = new DisableHeartbeat($this->hookDispatcher, $this->scriptManager, true, 120);

        $result = $heartbeat->setAdminInterval(['interval' => 15, 'minimalInterval' => 0]);

        $this->assertSame(120, $result['interval']);
        $this->assertSame(0, $result['minimalInterval']);
    }

    public function testSetAdminIntervalUsesDefaultInterval(): void
    {
        $heartbeat = new DisableHeartbeat($this->hookDispatcher, $this->scriptManager);

        $result = $heartbeat->setAdminInterval([]);

        $this->assertSame(60, $result['interval']);
    }
}
