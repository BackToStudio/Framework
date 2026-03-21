<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Contracts\HealthCheckStatus;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Hardening\HtmlEscapeTrait;

/**
 * Displays admin notices when the system is degraded or unhealthy.
 *
 * Hooks into 'admin_notices' and checks the latest health check results
 * to surface critical issues to site administrators.
 */
final class SystemNoticeManager implements Hooks
{
    use HtmlEscapeTrait;

    private readonly HealthCheckRegistry $registry;
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(
        HealthCheckRegistry $registry,
        HookDispatcherInterface $hookDispatcher,
    ) {
        $this->registry = $registry;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_notices', [$this, 'displayNotices']);
    }

    public function displayNotices(): void
    {
        if (!$this->currentUserCanManage()) {
            return;
        }

        $results = $this->registry->runAll();
        $unhealthy = [];
        $degraded = [];

        foreach ($results as $name => $result) {
            if ($result->getHealthCheckStatus() === HealthCheckStatus::Unhealthy) {
                $unhealthy[$name] = $result->getMessage();
            } elseif ($result->getHealthCheckStatus() === HealthCheckStatus::Degraded) {
                $degraded[$name] = $result->getMessage();
            }
        }

        if ($unhealthy !== []) {
            $this->renderNotice(
                'error',
                \sprintf(
                    '<strong>System Alert:</strong> %d component(s) unhealthy &mdash; %s. <a href="%s">View dashboard</a>',
                    \count($unhealthy),
                    $this->escapeHtml(\implode(', ', \array_keys($unhealthy))),
                    \admin_url('admin.php?page=backto-operations'),
                ),
            );
        }

        if ($degraded !== []) {
            $this->renderNotice(
                'warning',
                \sprintf(
                    '<strong>System Warning:</strong> %d component(s) degraded &mdash; %s. <a href="%s">View dashboard</a>',
                    \count($degraded),
                    $this->escapeHtml(\implode(', ', \array_keys($degraded))),
                    \admin_url('admin.php?page=backto-operations'),
                ),
            );
        }
    }

    private function renderNotice(string $type, string $html): void
    {
        echo '<div class="notice notice-' . $this->escapeAttr($type) . ' is-dismissible"><p>' . $html . '</p></div>';
    }

    protected function currentUserCanManage(): bool
    {
        return \function_exists('current_user_can') && \current_user_can('manage_options');
    }
}
