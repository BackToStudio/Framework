<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Control the WordPress Heartbeat API.
 *
 * Disables Heartbeat on the front-end and reduces frequency in admin
 * to minimize AJAX requests and server load.
 */
class DisableHeartbeat implements Hooks
{
    private HookDispatcherInterface $hookDispatcher;
    private bool $disableFrontend;
    private int $adminInterval;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        bool $disableFrontend = true,
        int $adminInterval = 60
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->disableFrontend = $disableFrontend;
        $this->adminInterval = $adminInterval;
    }

    public function hooks(): void
    {
        if ($this->disableFrontend) {
            $this->hookDispatcher->addAction('init', [$this, 'deregisterHeartbeatOnFrontend']);
        }

        $this->hookDispatcher->addFilter('heartbeat_settings', [$this, 'setAdminInterval']);
    }

    public function deregisterHeartbeatOnFrontend(): void
    {
        if (!\is_admin()) {
            \wp_deregister_script('heartbeat');
        }
    }

    /**
     * Set Heartbeat API interval in admin.
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function setAdminInterval(array $settings): array
    {
        $settings['interval'] = $this->adminInterval;

        return $settings;
    }
}
