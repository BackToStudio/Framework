<?php

namespace BackTo\Framework\Hooks;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\DeactivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RegistryInterface;

class HookRegistry implements RegistryInterface
{

    /**
     * @var HookInterface[]
     */
    protected $hooks = [];

    /**
     * @var string|null
     */
    private $pluginFile;

    /**
     * @var HookDispatcherInterface
     */
    private $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    /**
     * @return HookInterface[]
     */
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
        foreach ($this->getHooks() as $action) {
            if ($action instanceof Hooks) {
                $action->hooks();
            }

            if ($action instanceof AdminHooks && $this->hookDispatcher->isAdmin()) {
                $action->hooks();
            }

            if ($this->pluginFile !== null && $action instanceof ActivationHooks) {
                $this->hookDispatcher->registerActivationHook($this->pluginFile, [$action, 'activate']);
            }

            if ($this->pluginFile !== null && $action instanceof DeactivationHooks) {
                $this->hookDispatcher->registerDeactivationHook($this->pluginFile, [$action, 'deactivate']);
            }
        }
    }

}
