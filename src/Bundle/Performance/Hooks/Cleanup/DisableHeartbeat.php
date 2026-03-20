<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Hooks\Cleanup;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\QueryContextInterface;
use BackTo\Framework\Contracts\ScriptManagerInterface;

/**
 * Control the WordPress Heartbeat API.
 *
 * Disables Heartbeat on the front-end and reduces frequency in admin
 * to minimize AJAX requests and server load.
 */
final class DisableHeartbeat implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly QueryContextInterface $queryContext;
    private readonly ScriptManagerInterface $scriptManager;
    private readonly bool $disableFrontend;
    private readonly int $adminInterval;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        QueryContextInterface $queryContext,
        ScriptManagerInterface $scriptManager,
        bool $disableFrontend = true,
        int $adminInterval = 60
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->queryContext = $queryContext;
        $this->scriptManager = $scriptManager;
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
        if (!$this->queryContext->isAdmin()) {
            $this->scriptManager->deregisterScript('heartbeat');
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
