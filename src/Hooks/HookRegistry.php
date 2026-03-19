<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\DeactivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Hooks\Contracts\HookRegistryInterface;

final class HookRegistry implements HookRegistryInterface
{
    /** @var HookInterface[] */
    protected array $hooks = [];
    private ?string $pluginFile = null;
    private readonly HookDispatcherInterface $hookDispatcher;

    /** @var array<callable(HookInterface, HookDispatcherInterface, bool, ?string): void> */
    private array $hookRunners = [];

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
        $this->registerDefaultRunners();
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

    /**
     * Register a custom hook runner for extending hook execution.
     *
     * Each runner receives the hook, the dispatcher, the isAdmin flag,
     * and the plugin file path (or null for themes).
     *
     * This allows adding new hook types (e.g., CronHooks, RestHooks)
     * without modifying this class (Open/Closed Principle).
     *
     * @param callable(HookInterface, HookDispatcherInterface, bool, ?string): void $runner
     */
    public function addRunner(callable $runner): self
    {
        $this->hookRunners[] = $runner;

        return $this;
    }

    public function runHooks(): void
    {
        $isAdmin = $this->hookDispatcher->isAdmin();

        foreach ($this->getHooks() as $hook) {
            foreach ($this->hookRunners as $runner) {
                $runner($hook, $this->hookDispatcher, $isAdmin, $this->pluginFile);
            }
        }
    }

    private function registerDefaultRunners(): void
    {
        // Front/global hooks
        $this->addRunner(static function (HookInterface $hook): void {
            if ($hook instanceof Hooks) {
                $hook->hooks();
            }
        });

        // Admin-only hooks
        $this->addRunner(static function (HookInterface $hook, HookDispatcherInterface $dispatcher, bool $isAdmin): void {
            if ($isAdmin && $hook instanceof AdminHooks) {
                $hook->hooks();
            }
        });

        // Plugin activation hooks
        $this->addRunner(static function (HookInterface $hook, HookDispatcherInterface $dispatcher, bool $isAdmin, ?string $pluginFile): void {
            if ($pluginFile !== null && $hook instanceof ActivationHooks) {
                $dispatcher->registerActivationHook($pluginFile, [$hook, 'activate']);
            }
        });

        // Plugin deactivation hooks
        $this->addRunner(static function (HookInterface $hook, HookDispatcherInterface $dispatcher, bool $isAdmin, ?string $pluginFile): void {
            if ($pluginFile !== null && $hook instanceof DeactivationHooks) {
                $dispatcher->registerDeactivationHook($pluginFile, [$hook, 'deactivate']);
            }
        });
    }
}
