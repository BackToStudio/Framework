<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Controls WordPress automatic update behaviour for security hardening.
 *
 * Allows fine-grained control over auto-updates for:
 * - Core (major and minor)
 * - Plugins
 * - Themes
 * - Translations
 *
 * Default policy: minor core updates and translations enabled,
 * major core / plugins / themes disabled to prevent untested changes.
 */
final class AutoUpdatePolicy implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly LoggerInterface $logger;

    private bool $majorCore = false;
    private bool $minorCore = true;
    private bool $plugins = false;
    private bool $themes = false;
    private bool $translations = true;

    /** @var string[] Plugin basenames to force-enable auto-update */
    private array $allowedPlugins = [];

    /** @var string[] Theme slugs to force-enable auto-update */
    private array $allowedThemes = [];

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        LoggerInterface $logger,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->logger = $logger;
    }

    public function getName(): string
    {
        return 'auto_update_policy';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('allow_major_auto_core_updates', [$this, 'filterMajorCore']);
        $this->hookDispatcher->addFilter('allow_minor_auto_core_updates', [$this, 'filterMinorCore']);
        $this->hookDispatcher->addFilter('auto_update_plugin', [$this, 'filterPlugin'], 10, 2);
        $this->hookDispatcher->addFilter('auto_update_theme', [$this, 'filterTheme'], 10, 2);
        $this->hookDispatcher->addFilter('auto_update_translation', [$this, 'filterTranslation']);
    }

    public function setMajorCore(bool $enabled): self
    {
        $this->majorCore = $enabled;

        return $this;
    }

    public function setMinorCore(bool $enabled): self
    {
        $this->minorCore = $enabled;

        return $this;
    }

    public function setPlugins(bool $enabled): self
    {
        $this->plugins = $enabled;

        return $this;
    }

    public function setThemes(bool $enabled): self
    {
        $this->themes = $enabled;

        return $this;
    }

    public function setTranslations(bool $enabled): self
    {
        $this->translations = $enabled;

        return $this;
    }

    /**
     * @param string[] $basenames Plugin basenames (e.g. 'akismet/akismet.php')
     */
    public function setAllowedPlugins(array $basenames): self
    {
        $this->allowedPlugins = $basenames;

        return $this;
    }

    /**
     * @param string[] $slugs Theme directory names
     */
    public function setAllowedThemes(array $slugs): self
    {
        $this->allowedThemes = $slugs;

        return $this;
    }

    public function filterMajorCore(bool $update): bool
    {
        if ($update !== $this->majorCore) {
            $this->logger->info('Auto-update policy: major core updates ' . ($this->majorCore ? 'enabled' : 'blocked'));
        }

        return $this->majorCore;
    }

    public function filterMinorCore(bool $update): bool
    {
        return $this->minorCore;
    }

    /**
     * @param object $item Plugin update item with ->plugin property
     */
    public function filterPlugin(bool $update, object $item): bool
    {
        $plugin = $item->plugin ?? '';

        if ($this->allowedPlugins !== [] && in_array($plugin, $this->allowedPlugins, true)) {
            return true;
        }

        return $this->plugins;
    }

    /**
     * @param object $item Theme update item with ->theme property
     */
    public function filterTheme(bool $update, object $item): bool
    {
        $theme = $item->theme ?? '';

        if ($this->allowedThemes !== [] && in_array($theme, $this->allowedThemes, true)) {
            return true;
        }

        return $this->themes;
    }

    public function filterTranslation(bool $update): bool
    {
        return $this->translations;
    }

    public function isMajorCoreEnabled(): bool
    {
        return $this->majorCore;
    }

    public function isMinorCoreEnabled(): bool
    {
        return $this->minorCore;
    }

    public function arePluginsEnabled(): bool
    {
        return $this->plugins;
    }

    public function areThemesEnabled(): bool
    {
        return $this->themes;
    }

    public function areTranslationsEnabled(): bool
    {
        return $this->translations;
    }

    
    public function getAllowedPlugins(): array
    {
        return $this->allowedPlugins;
    }

    
    public function getAllowedThemes(): array
    {
        return $this->allowedThemes;
    }

    /**
     * Get a summary of the current policy.
     *
     * @return array{major_core: bool, minor_core: bool, plugins: bool, themes: bool, translations: bool, allowed_plugins: string[], allowed_themes: string[]}
     */
    public function getPolicy(): array
    {
        return [
            'major_core' => $this->majorCore,
            'minor_core' => $this->minorCore,
            'plugins' => $this->plugins,
            'themes' => $this->themes,
            'translations' => $this->translations,
            'allowed_plugins' => $this->allowedPlugins,
            'allowed_themes' => $this->allowedThemes,
        ];
    }
}
