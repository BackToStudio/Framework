<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\RestApi\Contracts\RestRouteRegistrarInterface;
use BackTo\Framework\RestApi\RegisterRestRoute;
use BackTo\Framework\RestApi\RestRouteRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass to control isUserLoggedIn().
 */
class TestableRegisterRestRoute extends RegisterRestRoute
{
    private bool $loggedIn = false;

    public function setLoggedIn(bool $loggedIn): void
    {
        $this->loggedIn = $loggedIn;
    }

    protected function isUserLoggedIn(): bool
    {
        return $this->loggedIn;
    }
}

class RegisterRestRouteTest extends TestCase
{
    public function testImplementsHooks(): void
    {
        $register = new RegisterRestRoute(
            new RestRouteRegistry(),
            $this->createMock(RestRouteRegistrarInterface::class),
            $this->createMock(HookDispatcherInterface::class),
        );

        $this->assertInstanceOf(Hooks::class, $register);
    }

    public function testHooksRegistersRestApiInitAction(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('rest_api_init', $this->anything());

        $register = new RegisterRestRoute(
            new RestRouteRegistry(),
            $this->createMock(RestRouteRegistrarInterface::class),
            $dispatcher,
        );

        $register->hooks();
    }

    public function testRegisterRoutesUsesSecureDefaultPermission(): void
    {
        $route = $this->createMock(RestRouteInterface::class);
        $route->method('getNamespace')->willReturn('myplugin/v1');
        $route->method('getRoute')->willReturn('/items');
        $route->method('getMethods')->willReturn(['GET']);
        $route->method('getPermissionCallback')->willReturn(null);

        $registry = new RestRouteRegistry();
        $registry->add($route);

        $registrar = $this->createMock(RestRouteRegistrarInterface::class);
        $registrar->expects($this->once())
            ->method('register')
            ->with(
                'myplugin/v1',
                '/items',
                $this->callback(function (array $args) {
                    return $args['methods'] === ['GET']
                        && is_array($args['permission_callback'])
                        && $args['permission_callback'][1] === 'requireAuthentication';
                }),
            );

        $register = new RegisterRestRoute(
            $registry,
            $registrar,
            $this->createMock(HookDispatcherInterface::class),
        );

        $register->registerRoutes();
    }

    public function testRegisterRoutesWithCustomPermissionCallback(): void
    {
        $callback = function () {
            return true;
        };

        $route = $this->createMock(RestRouteInterface::class);
        $route->method('getNamespace')->willReturn('myplugin/v1');
        $route->method('getRoute')->willReturn('/items');
        $route->method('getMethods')->willReturn(['POST']);
        $route->method('getPermissionCallback')->willReturn($callback);

        $registry = new RestRouteRegistry();
        $registry->add($route);

        $registrar = $this->createMock(RestRouteRegistrarInterface::class);
        $registrar->expects($this->once())
            ->method('register')
            ->with(
                'myplugin/v1',
                '/items',
                $this->callback(function (array $args) use ($callback) {
                    return $args['permission_callback'] === $callback;
                }),
            );

        $register = new RegisterRestRoute(
            $registry,
            $registrar,
            $this->createMock(HookDispatcherInterface::class),
        );

        $register->registerRoutes();
    }

    public function testRegisterRoutesWithEmptyRegistry(): void
    {
        $registrar = $this->createMock(RestRouteRegistrarInterface::class);
        $registrar->expects($this->never())->method('register');

        $register = new RegisterRestRoute(
            new RestRouteRegistry(),
            $registrar,
            $this->createMock(HookDispatcherInterface::class),
        );

        $register->registerRoutes();
    }

    public function testRequireAuthenticationDeniesUnauthenticated(): void
    {
        $register = new TestableRegisterRestRoute(
            new RestRouteRegistry(),
            $this->createMock(RestRouteRegistrarInterface::class),
            $this->createMock(HookDispatcherInterface::class),
        );

        $register->setLoggedIn(false);
        $this->assertFalse($register->requireAuthentication());
    }

    public function testRequireAuthenticationAllowsAuthenticated(): void
    {
        $register = new TestableRegisterRestRoute(
            new RestRouteRegistry(),
            $this->createMock(RestRouteRegistrarInterface::class),
            $this->createMock(HookDispatcherInterface::class),
        );

        $register->setLoggedIn(true);
        $this->assertTrue($register->requireAuthentication());
    }
}
