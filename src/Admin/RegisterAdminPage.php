<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin;

use BackTo\Framework\Admin\Contracts\AdminPageRegistrarInterface;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;

final class RegisterAdminPage implements AdminHooks
{
    private readonly AdminPageRegistry $registry;
    private readonly AdminPageRegistrarInterface $registrar;
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(
        AdminPageRegistry $registry,
        AdminPageRegistrarInterface $registrar,
        HookDispatcherInterface $hookDispatcher,
    ) {
        $this->registry = $registry;
        $this->registrar = $registrar;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_menu', [$this, 'registerAdminPages']);
    }

    public function registerAdminPages(): void
    {
        foreach ($this->registry->getPages() as $page) {
            $this->registrar->registerMenuPage([
                'page_title' => $page->getPageTitle(),
                'menu_title' => $page->getMenuTitle(),
                'capability' => $page->getCapability(),
                'menu_slug' => $page->getMenuSlug(),
                'callback' => [$page, 'render'],
                'icon_url' => $page->getIconUrl(),
                'position' => $page->getPosition(),
            ]);
        }
    }
}
