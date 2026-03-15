<?php

namespace BackTo\Framework\Admin;

use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;

use function add_menu_page;

class AddReusableBlockMenu implements AdminHooks {

	private HookDispatcherInterface $hookDispatcher;

	public function __construct(HookDispatcherInterface $hookDispatcher)
	{
		$this->hookDispatcher = $hookDispatcher;
	}

	public function hooks(): void {
		$this->hookDispatcher->addAction( 'admin_menu', [ $this, 'addReusableBlockMenu'] );
	}

	function addReusableBlockMenu(): void {
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
