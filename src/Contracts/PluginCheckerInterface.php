<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Port interface for checking whether a plugin is active.
 *
 * Abstracts is_plugin_active() so that application-layer code
 * does not call WordPress functions directly.
 */
interface PluginCheckerInterface
{
    public function isActive(string $pluginFile): bool;
}
