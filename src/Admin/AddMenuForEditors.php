<?php

namespace BackTo\Framework\Admin;

use BackTo\Framework\Contracts\AdminHooks;
use WP_Admin_Bar;

use function add_action;
use function current_user_can;
use function get_role;
use function remove_submenu_page;

class AddMenuForEditors implements AdminHooks {

	public function hooks() {
		add_action( 'admin_head', [ $this, 'displayAppearanceMenu'] );
		add_action( 'admin_bar_menu', [ $this, 'removeCustomizer'], 999 );
	}

	public function displayAppearanceMenu() {
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
	public function removeCustomizer( WP_Admin_Bar $wp_adminbar ) {
		$wp_adminbar->remove_node( 'customize' );
	}
}
