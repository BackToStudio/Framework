<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Bundle\Security\SecurityConfigurator;
use PHPUnit\Framework\TestCase;

class SecurityConfiguratorAutoUpdateTest extends TestCase
{
    private SecurityConfigurator $configurator;

    protected function setUp(): void
    {
        $this->configurator = new SecurityConfigurator();
    }

    public function testAutoUpdateMajorCore(): void
    {
        $result = $this->configurator->autoUpdateMajorCore(true);

        $this->assertSame($this->configurator, $result);
        $this->assertTrue($this->configurator->toParameters()['security.auto_update_major_core']);
    }

    public function testAutoUpdateMinorCore(): void
    {
        $this->configurator->autoUpdateMinorCore(false);
        $this->assertFalse($this->configurator->toParameters()['security.auto_update_minor_core']);
    }

    public function testAutoUpdatePlugins(): void
    {
        $this->configurator->autoUpdatePlugins(true);
        $this->assertTrue($this->configurator->toParameters()['security.auto_update_plugins']);
    }

    public function testAutoUpdateThemes(): void
    {
        $this->configurator->autoUpdateThemes(true);
        $this->assertTrue($this->configurator->toParameters()['security.auto_update_themes']);
    }

    public function testAutoUpdateTranslations(): void
    {
        $this->configurator->autoUpdateTranslations(false);
        $this->assertFalse($this->configurator->toParameters()['security.auto_update_translations']);
    }

    public function testAutoUpdateAllowedPlugins(): void
    {
        $result = $this->configurator->autoUpdateAllowedPlugins(['akismet/akismet.php', 'woo/woo.php']);

        $this->assertSame($this->configurator, $result);
        $this->assertSame(
            ['akismet/akismet.php', 'woo/woo.php'],
            $this->configurator->toParameters()['security.auto_update_allowed_plugins'],
        );
    }

    public function testAutoUpdateAllowedThemes(): void
    {
        $result = $this->configurator->autoUpdateAllowedThemes(['theme-a', 'theme-b']);

        $this->assertSame($this->configurator, $result);
        $this->assertSame(
            ['theme-a', 'theme-b'],
            $this->configurator->toParameters()['security.auto_update_allowed_themes'],
        );
    }

    public function testFluentChaining(): void
    {
        $params = $this->configurator
            ->autoUpdateMajorCore(false)
            ->autoUpdateMinorCore(true)
            ->autoUpdatePlugins(false)
            ->autoUpdateThemes(false)
            ->autoUpdateTranslations(true)
            ->autoUpdateAllowedPlugins(['akismet/akismet.php'])
            ->autoUpdateAllowedThemes(['twentytwentyfive'])
            ->toParameters();

        $this->assertCount(7, $params);
        $this->assertFalse($params['security.auto_update_major_core']);
        $this->assertTrue($params['security.auto_update_minor_core']);
        $this->assertSame(['akismet/akismet.php'], $params['security.auto_update_allowed_plugins']);
    }
}
