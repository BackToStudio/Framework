<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\UserContextInterface;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\RestApi\Contracts\RestRouteRegistrarInterface;
use BackTo\Framework\RestApi\RegisterRestRoute;
use BackTo\Framework\RestApi\RestRouteRegistry;
use PHPUnit\Framework\TestCase;

class RegisterRestRouteTest extends TestCase
{
    private UserContextInterface $userContext;
    private HookDispatcherInterface $hookDispatcher;

    protected function setUp(): void
    {
        $this->userContext = $this->createMock(UserContextInterface::class);
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
    }

    private function createRegister(
        ?RestRouteRegistry $registry = null,
        ?RestRouteRegistrarInterface $registrar = null,
    ): RegisterRestRoute {
        return new RegisterRestRoute(
            $registry ?? new RestRouteRegistry(),
            $registrar ?? $this->createMock(RestRouteRegistrarInterface::class),
            $this->hookDispatcher,
            $this->userContext,
        );
    }

    public function testImplementsHooks(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->createRegister());
    }

    public function testHooksRegistersRestApiInitAction(): void
    {
        $this->hookDispatcher->expects($this->once())
            ->method('addAction')
            ->with('rest_api_init', $this->anything());

        $this->createRegister()->hooks();
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
                        && $args['callback'] instanceof \Closure
                        && is_array($args['permission_callback'])
                        && $args['permission_callback'][1] === 'requireAuthentication';
                }),
            );

        $this->createRegister($registry, $registrar)->registerRoutes();
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
                    return $args['callback'] instanceof \Closure
                        && $args['permission_callback'] === $callback;
                }),
            );

        $this->createRegister($registry, $registrar)->registerRoutes();
    }

    public function testRegisterRoutesWithEmptyRegistry(): void
    {
        $registrar = $this->createMock(RestRouteRegistrarInterface::class);
        $registrar->expects($this->never())->method('register');

        $this->createRegister(registrar: $registrar)->registerRoutes();
    }

    public function testRequireAuthenticationDeniesUnauthenticated(): void
    {
        $this->userContext->method('isLoggedIn')->willReturn(false);

        $this->assertFalse($this->createRegister()->requireAuthentication());
    }

    public function testRequireAuthenticationAllowsAuthenticated(): void
    {
        $this->userContext->method('isLoggedIn')->willReturn(true);

        $this->assertTrue($this->createRegister()->requireAuthentication());
    }
}
