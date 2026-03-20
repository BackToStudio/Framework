<?php

declare(strict_types=1);

namespace BackTo\Framework\WordPress\Infrastructure;

use BackTo\Framework\Contracts\PluginCheckerInterface;

final class WordPressPluginChecker implements PluginCheckerInterface
{
    public function isActive(string $pluginFile): bool
    {
        return \is_plugin_active($pluginFile);
    }
}
