<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Disables the public wp-cron.php endpoint.
 *
 * WordPress fires cron jobs via an HTTP request to wp-cron.php on every page load.
 * This is a DDoS vector and should be replaced by a real server-side cron job:
 *   * * * * * cd /path/to/wp && php wp-cron.php --doing_wp_cron >/dev/null 2>&1
 */
class DisablePublicCron implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function getName(): string
    {
        return 'disable_public_cron';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'disableCron'], 1);
    }

    public function disableCron(): void
    {
        if (! $this->isCronDisabled()) {
            $this->defineDisableCron();
        }
    }

    protected function isCronDisabled(): bool
    {
        return defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
    }

    protected function defineDisableCron(): void
    {
        if (! defined('DISABLE_WP_CRON')) {
            define('DISABLE_WP_CRON', true);
        }
    }
}
