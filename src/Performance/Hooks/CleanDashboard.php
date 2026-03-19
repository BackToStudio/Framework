<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;

/**
 * Remove unnecessary dashboard widgets and meta boxes.
 *
 * Reduces admin AJAX requests and improves admin page load time.
 */
final class CleanDashboard implements AdminHooks
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('wp_dashboard_setup', [$this, 'removeDashboardWidgets']);
    }

    public function removeDashboardWidgets(): void
    {
        \remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
        \remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
        \remove_meta_box('dashboard_primary', 'dashboard', 'side');
        \remove_meta_box('dashboard_secondary', 'dashboard', 'side');
        \remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        \remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
        $this->hookDispatcher->removeAction('welcome_panel', 'wp_welcome_panel');
    }
}
