<?php

namespace Fantassin\Core\WordPress\Admin;

use Fantassin\Core\WordPress\HasAdminHooks;

class AddReusableBlockMenu implements HasAdminHooks {

	public function hooks() {
		add_action( 'admin_menu', [ $this, 'add_reusable_block_menu' ] );
	}

	function add_reusable_block_menu() {
		add_menu_page(
			__( 'Reusable Blocks', 'gutenberg' ),
			__( 'Reusable Blocks', 'gutenberg' ),
			'manage_options',
			'edit.php?post_type=wp_block',
			'',
			'dashicons-block-default',
			30
		);
	}
}
