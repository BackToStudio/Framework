<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstraction over WordPress script and style management.
 *
 * Replaces direct calls to wp_enqueue_script(), wp_dequeue_style(), etc.
 */
interface ScriptManagerInterface
{
    public function enqueueScript(string $handle): void;

    public function dequeueScript(string $handle): void;

    public function registerScript(string $handle, string $src, array $deps = [], string|bool|null $ver = false, bool $inFooter = false): void;

    public function deregisterScript(string $handle): void;

    public function enqueueStyle(string $handle): void;

    public function dequeueStyle(string $handle): void;

    public function registerStyle(string $handle, string $src, array $deps = [], string|bool|null $ver = false, string $media = 'all'): void;

    public function deregisterStyle(string $handle): void;
}
