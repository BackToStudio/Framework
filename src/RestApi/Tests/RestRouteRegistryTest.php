<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi\Tests;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\RestApi\RestRouteRegistry;
use PHPUnit\Framework\TestCase;

class RestRouteRegistryTest extends TestCase
{
    public function testImplementsRegistryInterface(): void
    {
        $registry = new RestRouteRegistry();
        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyRegistry(): void
    {
        $registry = new RestRouteRegistry();
        $this->assertCount(0, $registry->getRoutes());
    }

    public function testAddRoute(): void
    {
        $registry = new RestRouteRegistry();
        $route = $this->createMock(RestRouteInterface::class);

        $result = $registry->add($route);

        $this->assertSame($registry, $result);
        $this->assertCount(1, $registry->getRoutes());
        $this->assertSame($route, $registry->getRoutes()[0]);
    }
}
