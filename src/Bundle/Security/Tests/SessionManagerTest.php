<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\Auth\SessionManager;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private RequestContextInterface $requestContext;
    private SessionManager $rule;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->rule = new SessionManager($this->dispatcher, $this->requestContext);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->rule);
    }

    public function testGetName(): void
    {
        $this->assertSame('session_manager', $this->rule->getName());
    }

    public function testHooksRegistersFiltersAndActions(): void
    {
        $this->dispatcher->expects($this->exactly(2))
            ->method('addFilter')
            ->willReturnCallback(function (string $hook) {
                $this->assertContains($hook, ['attach_session_information', 'session_token_manager']);
            });

        $this->dispatcher->expects($this->once())
            ->method('addAction')
            ->with('wp_login', $this->anything(), 10, 2);

        $this->rule->hooks();
    }

    public function testAttachSessionInfo(): void
    {
        $this->requestContext->method('getRemoteAddr')->willReturn('192.168.1.1');
        $this->requestContext->method('getUserAgent')->willReturn('TestBrowser/1.0');

        $info = $this->rule->attachSessionInfo([]);

        $this->assertSame('192.168.1.1', $info['ip']);
        $this->assertSame('TestBrowser/1.0', $info['ua']);
        $this->assertArrayHasKey('created', $info);
        $this->assertIsInt($info['created']);
    }

    public function testAttachSessionInfoPreservesExisting(): void
    {
        $info = $this->rule->attachSessionInfo(['existing' => 'data']);

        $this->assertSame('data', $info['existing']);
        $this->assertArrayHasKey('ip', $info);
    }

    public function testDefaultMaxSessions(): void
    {
        $this->assertSame(1, $this->rule->getMaxSessions());
    }

    public function testCustomMaxSessions(): void
    {
        $rule = new SessionManager($this->dispatcher, $this->requestContext, 3);
        $this->assertSame(3, $rule->getMaxSessions());
    }

    public function testEnforceConcurrentSessionLimitSkipsNonObject(): void
    {
        // Should not throw when $user is not an object
        $this->rule->enforceConcurrentSessionLimit('admin', 'not_an_object');
        $this->assertTrue(true); // No exception
    }

    public function testEnforceConcurrentSessionLimitSkipsMissingId(): void
    {
        $user = new \stdClass();
        $this->rule->enforceConcurrentSessionLimit('admin', $user);
        $this->assertTrue(true); // No exception
    }

    public function testEnforceConcurrentSessionLimitSkipsZeroId(): void
    {
        $user = new \stdClass();
        $user->ID = 0;
        $this->rule->enforceConcurrentSessionLimit('admin', $user);
        $this->assertTrue(true); // No exception — zero ID is invalid
    }

    public function testEnforceConcurrentSessionLimitSkipsNegativeId(): void
    {
        $user = new \stdClass();
        $user->ID = -1;
        $this->rule->enforceConcurrentSessionLimit('admin', $user);
        $this->assertTrue(true); // No exception — negative ID is invalid
    }

    public function testEnforceConcurrentSessionLimitSkipsNullId(): void
    {
        $user = new \stdClass();
        $user->ID = null;
        $this->rule->enforceConcurrentSessionLimit('admin', $user);
        $this->assertTrue(true); // ID cast to int = 0, should be skipped
    }
}
