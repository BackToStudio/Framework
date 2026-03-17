<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Tests;

use BackTo\Framework\Cache\CacheConfigurator;
use BackTo\Framework\Contracts\ModuleConfiguratorInterface;
use PHPUnit\Framework\TestCase;

class CacheConfiguratorTest extends TestCase
{
    public function testImplementsModuleConfiguratorInterface(): void
    {
        $configurator = new CacheConfigurator();

        $this->assertInstanceOf(ModuleConfiguratorInterface::class, $configurator);
    }

    public function testToParametersReturnsEmptyArrayByDefault(): void
    {
        $configurator = new CacheConfigurator();

        $this->assertSame([], $configurator->toParameters());
    }

    public function testFluentApiReturnsSelf(): void
    {
        $configurator = new CacheConfigurator();

        $this->assertSame($configurator, $configurator->ttl(3600));
        $this->assertSame($configurator, $configurator->enabled(true));
    }

    public function testOnlyOverriddenValuesAreReturned(): void
    {
        $configurator = new CacheConfigurator();
        $configurator->ttl(7200);

        $params = $configurator->toParameters();

        $this->assertCount(1, $params);
        $this->assertSame(7200, $params['cache.ttl']);
    }

    public function testFullChainedConfiguration(): void
    {
        $configurator = (new CacheConfigurator())
            ->ttl(7200)
            ->enabled(false);

        $params = $configurator->toParameters();

        $this->assertSame(7200, $params['cache.ttl']);
        $this->assertSame(false, $params['cache.enabled']);
    }

    public function testLastCallWins(): void
    {
        $configurator = (new CacheConfigurator())
            ->ttl(3600)
            ->ttl(7200);

        $this->assertSame(7200, $configurator->toParameters()['cache.ttl']);
    }
}
