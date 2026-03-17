<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets\Tests;

use BackTo\Framework\Assets\AssetsConfigurator;
use BackTo\Framework\Contracts\ModuleConfiguratorInterface;
use PHPUnit\Framework\TestCase;

class AssetsConfiguratorTest extends TestCase
{
    public function testImplementsModuleConfiguratorInterface(): void
    {
        $configurator = new AssetsConfigurator();

        $this->assertInstanceOf(ModuleConfiguratorInterface::class, $configurator);
    }

    public function testToParametersReturnsEmptyArrayByDefault(): void
    {
        $configurator = new AssetsConfigurator();

        $this->assertSame([], $configurator->toParameters());
    }

    public function testFluentApiReturnsSelf(): void
    {
        $configurator = new AssetsConfigurator();

        $this->assertSame($configurator, $configurator->versionStrategy('timestamp'));
    }

    public function testOnlyOverriddenValuesAreReturned(): void
    {
        $configurator = new AssetsConfigurator();
        $configurator->versionStrategy('timestamp');

        $params = $configurator->toParameters();

        $this->assertCount(1, $params);
        $this->assertSame('timestamp', $params['assets.version_strategy']);
    }

    public function testLastCallWins(): void
    {
        $configurator = (new AssetsConfigurator())
            ->versionStrategy('file')
            ->versionStrategy('timestamp');

        $this->assertSame('timestamp', $configurator->toParameters()['assets.version_strategy']);
    }
}
