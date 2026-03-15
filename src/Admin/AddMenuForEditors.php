<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin;

use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use WP_Admin_Bar;

use function current_user_can;
use function get_role;
use function remove_submenu_page;

class AddMenuForEditors implements AdminHooks {

	private HookDispatcherInterface $hookDispatcher;

	public function __construct(HookDispatcherInterface $hookDispatcher)
	{
		$this->hookDispatcher = $hookDispatcher;
	}

	public function hooks(): void {
		$this->hookDispatcher->addAction( 'admin_head', [ $this, 'displayAppearanceMenu'] );
		$this->hookDispatcher->addAction( 'admin_bar_menu', [ $this, 'removeCustomizer'], 999 );
	}

	public function displayAppearanceMenu(): void {
		// Do this only once. Can go anywhere inside your functions.php file
		$role_object = get_role( 'editor' );
		$role_object->add_cap( 'edit_theme_options' );

		if ( current_user_can( 'editor' ) ) {
			/**
			 * Remove unneeded submenus
			 */
			remove_submenu_page( 'themes.php', 'themes.php' ); // hide the theme selection submenu
			remove_submenu_page( 'themes.php', 'widgets.php' ); // hide the widgets submenu
			remove_submenu_page( 'themes.php', 'customize.php?return=%2Fwp-admin%2F' ); // hide the customizer submenu
			remove_submenu_page( 'themes.php', 'customize.php?return=%2Fwp-admin%2Fnav-menus.php' ); // hide the customizer submenu
			remove_submenu_page( 'themes.php', 'customize.php?return=%2Fwp-admin%2Ftools.php' ); // hide the customizer submenu
			remove_submenu_page( 'themes.php', 'customize.php?return=%2Fwp-admin%2Ftools.php&#038;autofocus%5Bcontrol%5D=background_image' ); // hide the background submenu
		}
	}

	/**
	 * Remove customizer from admin bar
	 */
	public function removeCustomizer( WP_Admin_Bar $wp_adminbar ): void {
		$wp_adminbar->remove_node( 'customize' );
	}
}
