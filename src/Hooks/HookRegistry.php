<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\DeactivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RegistryInterface;

final class HookRegistry implements RegistryInterface
{
    /** @var HookInterface[] */
    protected array $hooks = [];
    private ?string $pluginFile = null;
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    
    public function getHooks(): array
    {
        return $this->hooks;
    }

    public function setPluginFile(string $pluginFile): self
    {
        $this->pluginFile = $pluginFile;

        return $this;
    }

    public function addHook(HookInterface $hook): self
    {
        $this->hooks[] = $hook;

        return $this;
    }

    public function runHooks(): void
    {
        $isAdmin = $this->hookDispatcher->isAdmin();
        $hasPluginFile = $this->pluginFile !== null;

        foreach ($this->getHooks() as $action) {
            if ($action instanceof Hooks) {
                $action->hooks();
            } elseif ($action instanceof AdminHooks && $isAdmin) {
                $action->hooks();
            }

            if ($hasPluginFile && $action instanceof ActivationHooks) {
                $this->hookDispatcher->registerActivationHook($this->pluginFile, [$action, 'activate']);
            }

            if ($hasPluginFile && $action instanceof DeactivationHooks) {
                $this->hookDispatcher->registerDeactivationHook($this->pluginFile, [$action, 'deactivate']);
            }
        }
    }
}
