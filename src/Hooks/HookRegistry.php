<?php

namespace Fantassin\Core\WordPress\Hooks;

use Fantassin\Core\WordPress\Contracts\HookInterface;
use Fantassin\Core\WordPress\Contracts\AdminHooks;
use Fantassin\Core\WordPress\Contracts\Hooks;
use Fantassin\Core\WordPress\Contracts\RegistryInterface;

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
