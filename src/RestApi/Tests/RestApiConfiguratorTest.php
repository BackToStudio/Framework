<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi\Tests;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;
use BackTo\Framework\RestApi\RestApiConfigurator;
use PHPUnit\Framework\TestCase;

class RestApiConfiguratorTest extends TestCase
{
    public function testImplementsModuleConfiguratorInterface(): void
    {
        $configurator = new RestApiConfigurator();

        $this->assertInstanceOf(ModuleConfiguratorInterface::class, $configurator);
    }

    public function testToParametersReturnsEmptyArrayByDefault(): void
    {
        $configurator = new RestApiConfigurator();

        $this->assertSame([], $configurator->toParameters());
    }

    public function testFluentApiReturnsSelf(): void
    {
        $configurator = new RestApiConfigurator();

        $this->assertSame($configurator, $configurator->defaultNamespace('app/v1'));
        $this->assertSame($configurator, $configurator->defaultPerPage(10));
    }

    public function testOnlyOverriddenValuesAreReturned(): void
    {
        $configurator = new RestApiConfigurator();
        $configurator->defaultPerPage(25);

        $params = $configurator->toParameters();

        $this->assertCount(1, $params);
        $this->assertSame(25, $params['rest_api.default_per_page']);
    }

    public function testFullChainedConfiguration(): void
    {
        $configurator = (new RestApiConfigurator())
            ->defaultNamespace('custom/v2')
            ->defaultPerPage(25);

        $params = $configurator->toParameters();

        $this->assertSame('custom/v2', $params['rest_api.default_namespace']);
        $this->assertSame(25, $params['rest_api.default_per_page']);
    }

    public function testLastCallWins(): void
    {
        $configurator = (new RestApiConfigurator())
            ->defaultPerPage(10)
            ->defaultPerPage(50);

        $this->assertSame(50, $configurator->toParameters()['rest_api.default_per_page']);
    }
}
