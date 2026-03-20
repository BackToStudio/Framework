<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;
use BackTo\Framework\Bundle\Seo\SeoConfigurator;
use PHPUnit\Framework\TestCase;

class SeoConfiguratorTest extends TestCase
{
    public function testImplementsModuleConfiguratorInterface(): void
    {
        $configurator = new SeoConfigurator();

        $this->assertInstanceOf(ModuleConfiguratorInterface::class, $configurator);
    }

    public function testToParametersReturnsEmptyArrayByDefault(): void
    {
        $configurator = new SeoConfigurator();

        $this->assertSame([], $configurator->toParameters());
    }

    public function testFluentApiReturnsSelf(): void
    {
        $configurator = new SeoConfigurator();

        $this->assertSame($configurator, $configurator->titleSeparator('|'));
        $this->assertSame($configurator, $configurator->robotsDefault('index, follow'));
    }

    public function testOnlyOverriddenValuesAreReturned(): void
    {
        $configurator = new SeoConfigurator();
        $configurator->titleSeparator('-');

        $params = $configurator->toParameters();

        $this->assertCount(1, $params);
        $this->assertSame('-', $params['seo.title_separator']);
    }

    public function testFullChainedConfiguration(): void
    {
        $configurator = (new SeoConfigurator())
            ->titleSeparator('-')
            ->robotsDefault('noindex, nofollow');

        $params = $configurator->toParameters();

        $this->assertSame('-', $params['seo.title_separator']);
        $this->assertSame('noindex, nofollow', $params['seo.robots_default']);
    }

    public function testLastCallWins(): void
    {
        $configurator = (new SeoConfigurator())
            ->titleSeparator('-')
            ->titleSeparator('~');

        $this->assertSame('~', $configurator->toParameters()['seo.title_separator']);
    }
}
