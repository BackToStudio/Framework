<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;
use BackTo\Framework\Observability\ObservabilityConfigurator;
use PHPUnit\Framework\TestCase;

class ObservabilityConfiguratorTest extends TestCase
{
    public function testImplementsModuleConfiguratorInterface(): void
    {
        $configurator = new ObservabilityConfigurator();

        $this->assertInstanceOf(ModuleConfiguratorInterface::class, $configurator);
    }

    public function testToParametersReturnsEmptyArrayByDefault(): void
    {
        $configurator = new ObservabilityConfigurator();

        $this->assertSame([], $configurator->toParameters());
    }

    public function testFluentApiReturnsSelf(): void
    {
        $configurator = new ObservabilityConfigurator();

        $this->assertSame($configurator, $configurator->logLevel('debug'));
        $this->assertSame($configurator, $configurator->performanceTracking(true));
    }

    public function testOnlyOverriddenValuesAreReturned(): void
    {
        $configurator = new ObservabilityConfigurator();
        $configurator->logLevel('debug');

        $params = $configurator->toParameters();

        $this->assertCount(1, $params);
        $this->assertSame('debug', $params['observability.log_level']);
    }

    public function testFullChainedConfiguration(): void
    {
        $configurator = (new ObservabilityConfigurator())
            ->logLevel('debug')
            ->performanceTracking(true);

        $params = $configurator->toParameters();

        $this->assertSame('debug', $params['observability.log_level']);
        $this->assertSame(true, $params['observability.performance_tracking']);
    }

    public function testLastCallWins(): void
    {
        $configurator = (new ObservabilityConfigurator())
            ->logLevel('info')
            ->logLevel('debug');

        $this->assertSame('debug', $configurator->toParameters()['observability.log_level']);
    }
}
