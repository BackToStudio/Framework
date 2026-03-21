<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Cache\Contracts\TransientStoreInterface;
use BackTo\Framework\Contracts\HealthCheckStatus;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Hardening\HtmlEscapeTrait;

/**
 * Displays admin notices when the system is degraded or unhealthy.
 *
 * Hooks into 'admin_notices' and reads cached health check results
 * to surface critical issues to site administrators. Results are
 * cached for 5 minutes to avoid running health checks on every
 * admin page load.
 */
final class SystemNoticeManager implements Hooks
{
    use HtmlEscapeTrait;

    private const CACHE_KEY = 'backto_health_status';
    private const CACHE_TTL = 300; // 5 minutes

    private readonly HealthCheckRegistry $registry;
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly TransientStoreInterface $transientStore;

    public function __construct(
        HealthCheckRegistry $registry,
        HookDispatcherInterface $hookDispatcher,
        TransientStoreInterface $transientStore,
    ) {
        $this->registry = $registry;
        $this->hookDispatcher = $hookDispatcher;
        $this->transientStore = $transientStore;
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

        $status = $this->getCachedStatus();

        if ($status['unhealthy'] !== []) {
            $this->renderNotice(
                'error',
                \sprintf(
                    '<strong>System Alert:</strong> %d component(s) unhealthy &mdash; %s. <a href="%s">View dashboard</a>',
                    \count($status['unhealthy']),
                    $this->escapeHtml(\implode(', ', \array_keys($status['unhealthy']))),
                    \admin_url('admin.php?page=backto-operations'),
                ),
            );
        }

        if ($status['degraded'] !== []) {
            $this->renderNotice(
                'warning',
                \sprintf(
                    '<strong>System Warning:</strong> %d component(s) degraded &mdash; %s. <a href="%s">View dashboard</a>',
                    \count($status['degraded']),
                    $this->escapeHtml(\implode(', ', \array_keys($status['degraded']))),
                    \admin_url('admin.php?page=backto-operations'),
                ),
            );
        }
    }

    /**
     * @return array{unhealthy: array<string, string>, degraded: array<string, string>}
     */
    private function getCachedStatus(): array
    {
        $cached = $this->transientStore->get(self::CACHE_KEY);

        if (\is_array($cached) && isset($cached['unhealthy'], $cached['degraded'])) {
            return $cached;
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

        $status = ['unhealthy' => $unhealthy, 'degraded' => $degraded];
        $this->transientStore->set(self::CACHE_KEY, $status, self::CACHE_TTL);

        return $status;
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
