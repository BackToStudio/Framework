<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks\Contracts;

use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Contracts\RegistryInterface;

/**
 * Contract for the hook registry that collects and executes hooks.
 *
 * Extracted to respect the Dependency Inversion Principle:
 * the kernel depends on this abstraction, not on the concrete HookRegistry.
 */
interface HookRegistryInterface extends RegistryInterface
{
    public function addHook(HookInterface $hook): self;

    /**
     * @return HookInterface[]
     */
    public function getHooks(): array;

    public function setPluginFile(string $pluginFile): self;

    public function runHooks(): void;
}
