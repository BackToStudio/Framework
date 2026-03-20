# Conditionally show menu items based on user capabilities

Use `CapabilityManagerInterface` to check permissions before rendering:

```php
<?php

namespace MyPlugin\Admin;

use BackTo\Framework\Bundle\Admin\Contracts\CapabilityManagerInterface;
use BackTo\Framework\Bundle\Admin\Contracts\AdminPageRegistrarInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;

final class ConditionalMenus
{
    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
        private readonly AdminPageRegistrarInterface $registrar,
        private readonly CapabilityManagerInterface $capabilityManager,
    ) {}

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_menu', [$this, 'registerMenus']);
    }

    public function registerMenus(): void
    {
        // Always visible to admins
        $this->registrar->registerMenuPage([
            'page_title' => 'Dashboard',
            'menu_title' => 'My Plugin',
            'capability' => 'manage_options',
            'menu_slug'  => 'my-plugin',
            'callback'   => [$this, 'renderDashboard'],
            'icon_url'   => 'dashicons-chart-bar',
        ]);

        // Only for users who can edit others' posts (editors+)
        $this->registrar->registerSubmenuPage('my-plugin', [
            'page_title' => 'Content Reports',
            'menu_title' => 'Reports',
            'capability' => 'edit_others_posts',
            'menu_slug'  => 'my-plugin-reports',
            'callback'   => [$this, 'renderReports'],
        ]);

        // Hide advanced submenu from non-admins
        if (!$this->capabilityManager->currentUserCan('manage_options')) {
            $this->capabilityManager->removeSubmenuPage('my-plugin', 'my-plugin-advanced');
        }
    }
}
```
