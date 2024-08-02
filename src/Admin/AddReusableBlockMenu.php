<?php

namespace BackTo\Framework\Admin;

use BackTo\Framework\Contracts\AdminHooks;

use function add_action;
use function add_menu_page;

class AddReusableBlockMenu implements AdminHooks {

	public function hooks() {
		add_action( 'admin_menu', [ $this, 'addReusableBlockMenu'] );
	}

	function addReusableBlockMenu() {
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
