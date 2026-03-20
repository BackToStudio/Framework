# Create a full admin page class with DI

Implement `AdminPageInterface` and let the framework auto-register it via the `wordpress.admin_page` tag:

```php
<?php

namespace MyPlugin\Admin;

use BackTo\Framework\Bundle\Admin\Contracts\AdminPageInterface;

final class SettingsPage implements AdminPageInterface
{
    public function __construct(
        private readonly \BackTo\Framework\Contracts\HookDispatcherInterface $hookDispatcher,
    ) {}

    public function getPageTitle(): string { return 'My Plugin Settings'; }
    public function getMenuTitle(): string { return 'Settings'; }
    public function getCapability(): string { return 'manage_options'; }
    public function getMenuSlug(): string { return 'my-plugin-settings'; }
    public function getIconUrl(): string { return 'dashicons-admin-settings'; }
    public function getPosition(): ?int { return 80; }

    public function hooks(): void
    {
        // Additional hooks if needed (e.g. enqueue admin scripts)
    }

    public function render(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('my_plugin_options');
        do_settings_sections('my-plugin-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}
```

The `RegisterAdminPage` service picks up all services tagged `wordpress.admin_page` from the `AdminPageRegistry` and registers them automatically on `admin_menu`.
