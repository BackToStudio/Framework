<?php

declare(strict_types=1);

namespace BackTo\Framework\WordPress\Infrastructure;

use BackTo\Framework\Contracts\ScriptManagerInterface;

final class WordPressScriptManager implements ScriptManagerInterface
{
    public function enqueueScript(string $handle): void
    {
        \wp_enqueue_script($handle);
    }

    public function dequeueScript(string $handle): void
    {
        \wp_dequeue_script($handle);
    }

    public function registerScript(string $handle, string $src, array $deps = [], string|bool|null $ver = false, bool $inFooter = false): void
    {
        \wp_register_script($handle, $src, $deps, $ver, $inFooter);
    }

    public function deregisterScript(string $handle): void
    {
        \wp_deregister_script($handle);
    }

    public function enqueueStyle(string $handle): void
    {
        \wp_enqueue_style($handle);
    }

    public function dequeueStyle(string $handle): void
    {
        \wp_dequeue_style($handle);
    }

    public function registerStyle(string $handle, string $src, array $deps = [], string|bool|null $ver = false, string $media = 'all'): void
    {
        \wp_register_style($handle, $src, $deps, $ver, $media);
    }

    public function deregisterStyle(string $handle): void
    {
        \wp_deregister_style($handle);
    }
}
