<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Plugin\Infrastructure;

use BackTo\Framework\Bundle\Plugin\Contracts\TextDomainLoaderInterface;

use function load_muplugin_textdomain;
use function load_plugin_textdomain;
use function load_theme_textdomain;

/**
 * WordPress adapter for text domain loading.
 */
final class WordPressTextDomainLoader implements TextDomainLoaderInterface
{
    public function loadPluginTextDomain(string $domain, string $pluginRelPath): void
    {
        load_plugin_textdomain($domain, false, $pluginRelPath);
    }

    public function loadMuPluginTextDomain(string $domain, string $muPluginRelPath): void
    {
        load_muplugin_textdomain($domain, $muPluginRelPath);
    }

    public function loadThemeTextDomain(string $domain, string $path): void
    {
        load_theme_textdomain($domain, $path);
    }
}
