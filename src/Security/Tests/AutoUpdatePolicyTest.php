<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\AutoUpdatePolicy;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use PHPUnit\Framework\TestCase;

class AutoUpdatePolicyTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private LoggerInterface $logger;
    private AutoUpdatePolicy $policy;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->policy = new AutoUpdatePolicy($this->dispatcher, $this->logger);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->policy);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->policy);
    }

    public function testGetName(): void
    {
        $this->assertSame('auto_update_policy', $this->policy->getName());
    }

    public function testHooksRegistersFilters(): void
    {
        $this->dispatcher->expects($this->exactly(5))->method('addFilter');

        $this->policy->hooks();
    }

    public function testDefaultPolicy(): void
    {
        $this->assertFalse($this->policy->isMajorCoreEnabled());
        $this->assertTrue($this->policy->isMinorCoreEnabled());
        $this->assertFalse($this->policy->arePluginsEnabled());
        $this->assertFalse($this->policy->areThemesEnabled());
        $this->assertTrue($this->policy->areTranslationsEnabled());
    }

    public function testSetMajorCore(): void
    {
        $result = $this->policy->setMajorCore(true);
        $this->assertTrue($this->policy->isMajorCoreEnabled());
        $this->assertSame($this->policy, $result);
    }

    public function testSetMinorCore(): void
    {
        $result = $this->policy->setMinorCore(false);
        $this->assertFalse($this->policy->isMinorCoreEnabled());
        $this->assertSame($this->policy, $result);
    }

    public function testSetPlugins(): void
    {
        $result = $this->policy->setPlugins(true);
        $this->assertTrue($this->policy->arePluginsEnabled());
        $this->assertSame($this->policy, $result);
    }

    public function testSetThemes(): void
    {
        $result = $this->policy->setThemes(true);
        $this->assertTrue($this->policy->areThemesEnabled());
        $this->assertSame($this->policy, $result);
    }

    public function testSetTranslations(): void
    {
        $result = $this->policy->setTranslations(false);
        $this->assertFalse($this->policy->areTranslationsEnabled());
        $this->assertSame($this->policy, $result);
    }

    public function testFilterMajorCoreBlocksByDefault(): void
    {
        $this->assertFalse($this->policy->filterMajorCore(true));
    }

    public function testFilterMajorCoreAllowsWhenEnabled(): void
    {
        $this->policy->setMajorCore(true);
        $this->assertTrue($this->policy->filterMajorCore(false));
    }

    public function testFilterMajorCoreLogsWhenOverriding(): void
    {
        $this->logger->expects($this->once())->method('info');
        $this->policy->filterMajorCore(true); // default is false, so overriding true→false
    }

    public function testFilterMinorCoreAllowsByDefault(): void
    {
        $this->assertTrue($this->policy->filterMinorCore(false));
    }

    public function testFilterMinorCoreBlocksWhenDisabled(): void
    {
        $this->policy->setMinorCore(false);
        $this->assertFalse($this->policy->filterMinorCore(true));
    }

    public function testFilterPluginBlocksByDefault(): void
    {
        $item = (object) ['plugin' => 'akismet/akismet.php'];
        $this->assertFalse($this->policy->filterPlugin(true, $item));
    }

    public function testFilterPluginAllowsWhenEnabled(): void
    {
        $this->policy->setPlugins(true);
        $item = (object) ['plugin' => 'akismet/akismet.php'];
        $this->assertTrue($this->policy->filterPlugin(false, $item));
    }

    public function testFilterPluginAllowsAllowlistedPlugin(): void
    {
        $this->policy->setAllowedPlugins(['akismet/akismet.php']);
        $item = (object) ['plugin' => 'akismet/akismet.php'];
        $this->assertTrue($this->policy->filterPlugin(false, $item));
    }

    public function testFilterPluginBlocksNonAllowlistedPlugin(): void
    {
        $this->policy->setAllowedPlugins(['akismet/akismet.php']);
        $item = (object) ['plugin' => 'other/other.php'];
        $this->assertFalse($this->policy->filterPlugin(false, $item));
    }

    public function testFilterThemeBlocksByDefault(): void
    {
        $item = (object) ['theme' => 'twentytwentyfive'];
        $this->assertFalse($this->policy->filterTheme(true, $item));
    }

    public function testFilterThemeAllowsWhenEnabled(): void
    {
        $this->policy->setThemes(true);
        $item = (object) ['theme' => 'twentytwentyfive'];
        $this->assertTrue($this->policy->filterTheme(false, $item));
    }

    public function testFilterThemeAllowsAllowlistedTheme(): void
    {
        $this->policy->setAllowedThemes(['twentytwentyfive']);
        $item = (object) ['theme' => 'twentytwentyfive'];
        $this->assertTrue($this->policy->filterTheme(false, $item));
    }

    public function testFilterThemeBlocksNonAllowlistedTheme(): void
    {
        $this->policy->setAllowedThemes(['twentytwentyfive']);
        $item = (object) ['theme' => 'custom-theme'];
        $this->assertFalse($this->policy->filterTheme(false, $item));
    }

    public function testFilterTranslationAllowsByDefault(): void
    {
        $this->assertTrue($this->policy->filterTranslation(false));
    }

    public function testFilterTranslationBlocksWhenDisabled(): void
    {
        $this->policy->setTranslations(false);
        $this->assertFalse($this->policy->filterTranslation(true));
    }

    public function testSetAllowedPlugins(): void
    {
        $result = $this->policy->setAllowedPlugins(['a/a.php', 'b/b.php']);
        $this->assertSame(['a/a.php', 'b/b.php'], $this->policy->getAllowedPlugins());
        $this->assertSame($this->policy, $result);
    }

    public function testSetAllowedThemes(): void
    {
        $result = $this->policy->setAllowedThemes(['theme-a', 'theme-b']);
        $this->assertSame(['theme-a', 'theme-b'], $this->policy->getAllowedThemes());
        $this->assertSame($this->policy, $result);
    }

    public function testGetPolicySummary(): void
    {
        $this->policy->setMajorCore(true);
        $this->policy->setAllowedPlugins(['test/test.php']);

        $summary = $this->policy->getPolicy();

        $this->assertTrue($summary['major_core']);
        $this->assertTrue($summary['minor_core']);
        $this->assertFalse($summary['plugins']);
        $this->assertFalse($summary['themes']);
        $this->assertTrue($summary['translations']);
        $this->assertSame(['test/test.php'], $summary['allowed_plugins']);
        $this->assertSame([], $summary['allowed_themes']);
    }

    public function testFilterPluginHandlesMissingPluginProperty(): void
    {
        $item = new \stdClass();
        $this->assertFalse($this->policy->filterPlugin(false, $item));
    }

    public function testFilterThemeHandlesMissingThemeProperty(): void
    {
        $item = new \stdClass();
        $this->assertFalse($this->policy->filterTheme(false, $item));
    }
}
