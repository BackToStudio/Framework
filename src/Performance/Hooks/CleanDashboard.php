<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\AdminHooks;

/**
 * Remove unnecessary dashboard widgets and meta boxes.
 *
 * Reduces admin AJAX requests and improves admin page load time.
 */
final class CleanDashboard implements AdminHooks
{
    public function hooks(): void
    {
        \add_action('wp_dashboard_setup', [$this, 'removeDashboardWidgets']);
    }

    public function removeDashboardWidgets(): void
    {
        \remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
        \remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
        \remove_meta_box('dashboard_primary', 'dashboard', 'side');
        \remove_meta_box('dashboard_secondary', 'dashboard', 'side');
        \remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        \remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
        \remove_action('welcome_panel', 'wp_welcome_panel');
    }
}
