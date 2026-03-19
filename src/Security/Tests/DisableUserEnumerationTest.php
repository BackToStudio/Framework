<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Contracts\UserContextInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\DisableUserEnumeration;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass that controls isUserLoggedIn() return value.
 */
class TestableDisableUserEnumeration extends DisableUserEnumeration
{
    private bool $loggedIn = false;

    public function setUserLoggedIn(bool $loggedIn): void
    {
        $this->loggedIn = $loggedIn;
    }

    protected function isUserLoggedIn(): bool
    {
        return $this->loggedIn;
    }
}

class DisableUserEnumerationTest extends TestCase
{
    public function testImplementsRequiredInterfaces(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $requestContext = $this->createMock(RequestContextInterface::class);
        $rule = new DisableUserEnumeration($dispatcher, $requestContext, $this->createMock(ResponseEmitterInterface::class), $this->createMock(UserContextInterface::class));

        $this->assertInstanceOf(Hooks::class, $rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $rule);
    }

    public function testGetName(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $requestContext = $this->createMock(RequestContextInterface::class);
        $rule = new DisableUserEnumeration($dispatcher, $requestContext, $this->createMock(ResponseEmitterInterface::class), $this->createMock(UserContextInterface::class));

        $this->assertSame('disable_user_enumeration', $rule->getName());
    }

    public function testHooksRegistersActionAndFilter(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);

        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', $this->anything());

        $dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('rest_endpoints', $this->anything());

        $requestContext = $this->createMock(RequestContextInterface::class);
        $rule = new DisableUserEnumeration($dispatcher, $requestContext, $this->createMock(ResponseEmitterInterface::class), $this->createMock(UserContextInterface::class));
        $rule->hooks();
    }

    public function testRestrictUserEndpointsRemovesUsersForGuests(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $requestContext = $this->createMock(RequestContextInterface::class);
        $rule = new TestableDisableUserEnumeration($dispatcher, $requestContext, $this->createMock(ResponseEmitterInterface::class), $this->createMock(UserContextInterface::class));
        $rule->setUserLoggedIn(false);

        $endpoints = [
            '/wp/v2/posts' => ['methods' => 'GET'],
            '/wp/v2/users' => ['methods' => 'GET'],
            '/wp/v2/users/(?P<id>[\d]+)' => ['methods' => 'GET'],
            '/wp/v2/pages' => ['methods' => 'GET'],
        ];

        $result = $rule->restrictUserEndpoints($endpoints);

        $this->assertArrayNotHasKey('/wp/v2/users', $result);
        $this->assertArrayNotHasKey('/wp/v2/users/(?P<id>[\d]+)', $result);
        $this->assertArrayHasKey('/wp/v2/posts', $result);
        $this->assertArrayHasKey('/wp/v2/pages', $result);
    }

    public function testRestrictUserEndpointsKeepsUsersForLoggedIn(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $requestContext = $this->createMock(RequestContextInterface::class);
        $rule = new TestableDisableUserEnumeration($dispatcher, $requestContext, $this->createMock(ResponseEmitterInterface::class), $this->createMock(UserContextInterface::class));
        $rule->setUserLoggedIn(true);

        $endpoints = [
            '/wp/v2/posts' => ['methods' => 'GET'],
            '/wp/v2/users' => ['methods' => 'GET'],
        ];

        $result = $rule->restrictUserEndpoints($endpoints);

        $this->assertArrayHasKey('/wp/v2/users', $result);
        $this->assertArrayHasKey('/wp/v2/posts', $result);
    }
}
