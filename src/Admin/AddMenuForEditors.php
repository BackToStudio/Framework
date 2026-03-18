<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin;

use BackTo\Framework\Admin\Contracts\CapabilityManagerInterface;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use WP_Admin_Bar;

final class AddMenuForEditors implements AdminHooks
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly CapabilityManagerInterface $capabilityManager;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        CapabilityManagerInterface $capabilityManager,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->capabilityManager = $capabilityManager;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_head', [$this, 'displayAppearanceMenu']);
        $this->hookDispatcher->addAction('admin_bar_menu', [$this, 'removeCustomizer'], 999);
    }

    public function displayAppearanceMenu(): void
    {
        $this->capabilityManager->addCapToRole('editor', 'edit_theme_options');

        if ($this->capabilityManager->currentUserCan('editor')) {
            $this->capabilityManager->removeSubmenuPage('themes.php', 'themes.php');
            $this->capabilityManager->removeSubmenuPage('themes.php', 'widgets.php');
            $this->capabilityManager->removeSubmenuPage('themes.php', 'customize.php?return=%2Fwp-admin%2F');
            $this->capabilityManager->removeSubmenuPage('themes.php', 'customize.php?return=%2Fwp-admin%2Fnav-menus.php');
            $this->capabilityManager->removeSubmenuPage('themes.php', 'customize.php?return=%2Fwp-admin%2Ftools.php');
            $this->capabilityManager->removeSubmenuPage('themes.php', 'customize.php?return=%2Fwp-admin%2Ftools.php&#038;autofocus%5Bcontrol%5D=background_image');
        }
    }

    /**
     * Remove customizer from admin bar
     */
    public function removeCustomizer(WP_Admin_Bar $wp_adminbar): void
    {
        $wp_adminbar->remove_node('customize');
    }
}
