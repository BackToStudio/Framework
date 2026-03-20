<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Admin;

use BackTo\Framework\Bundle\Admin\Contracts\AdminPageRegistrarInterface;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;

final class AddReusableBlockMenu implements AdminHooks
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly AdminPageRegistrarInterface $registrar;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        AdminPageRegistrarInterface $registrar,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->registrar = $registrar;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_menu', [$this, 'addReusableBlockMenu']);
    }

    public function addReusableBlockMenu(): void
    {
        $this->registrar->registerMenuPage([
            'page_title' => __('Reusable Blocks', 'gutenberg'),
            'menu_title' => __('Reusable Blocks', 'gutenberg'),
            'capability' => 'manage_options',
            'menu_slug' => 'edit.php?post_type=wp_block',
            'icon_url' => 'dashicons-block-default',
            'position' => 30,
        ]);
    }
}
