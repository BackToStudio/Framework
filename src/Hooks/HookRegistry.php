<?php

namespace BackTo\Framework\Hooks;

use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RegistryInterface;

class HookRegistry implements RegistryInterface
{

    /**
     * @var array
     */
    protected $hooks = [];

    /**
     * @return array
     */
    public function getHooks(): array
    {
        return $this->hooks;
    }

    public function addHook(HookInterface $hook)
    {
        $this->hooks[] = $hook;

        return $this;
    }

    public function runHooks()
    {
        foreach ($this->getHooks() as $action) {
            if ($action instanceof Hooks) {
                $action->hooks();
            }

            if ($action instanceof AdminHooks && \is_admin()) {
                $action->hooks();
            }

//            if ($action instanceof ActivationHooks) {
//                register_activation_hook($this->pluginFile, [$action, 'activate']);
//            }
//
//            if ($action instanceof DeactivationHooks) {
//                register_deactivation_hook($this->pluginFile, [$action, 'deactivate']);
//            }
        }
    }

}
