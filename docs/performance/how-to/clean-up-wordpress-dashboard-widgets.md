# Clean up WordPress dashboard widgets

`CleanDashboard` removes default dashboard widgets that slow down admin page loads:

```php
<?php

$services->set(\BackTo\Framework\Bundle\Performance\Hooks\Cleanup\CleanDashboard::class)
    ->autowire()
    ->autoconfigure();
```

Widgets removed: `dashboard_incoming_links`, `dashboard_plugins`, `dashboard_primary`, `dashboard_secondary`, `dashboard_quick_press`, `dashboard_recent_drafts`, and the Welcome Panel.

To keep some widgets, create your own class that selectively removes only what you need:

```php
<?php

namespace MyPlugin\Performance;

use BackTo\Framework\Contracts\HookDispatcherInterface;

final class SelectiveDashboardCleanup
{
    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
    ) {}

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('wp_dashboard_setup', [$this, 'cleanup']);
    }

    public function cleanup(): void
    {
        remove_meta_box('dashboard_primary', 'dashboard', 'side');    // WordPress News
        remove_meta_box('dashboard_secondary', 'dashboard', 'side');  // WordPress Events
    }
}
```
