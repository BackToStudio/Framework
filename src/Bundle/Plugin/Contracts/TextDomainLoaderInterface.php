<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Plugin\Contracts;

/**
 * Port interface for loading translation text domains.
 *
 * Abstracts load_plugin_textdomain / load_muplugin_textdomain / load_theme_textdomain
 * so that I18n classes do not call WordPress functions directly.
 */
interface TextDomainLoaderInterface
{
    public function loadPluginTextDomain(string $domain, string $pluginRelPath): void;

    public function loadMuPluginTextDomain(string $domain, string $muPluginRelPath): void;

    public function loadThemeTextDomain(string $domain, string $path): void;
}
