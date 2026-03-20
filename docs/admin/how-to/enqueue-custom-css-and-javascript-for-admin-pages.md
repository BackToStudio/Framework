# Enqueue custom CSS and JavaScript for admin pages


Load assets only on your plugin's admin pages to avoid conflicts:

```php
<?php

namespace MyPlugin\Admin;

use BackTo\Framework\Contracts\HookDispatcherInterface;

final class AdminAssets
{
    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
        private readonly string $pluginDirectory,
    ) {}

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(string $hookSuffix): void
    {
        // Only load on our admin pages
        if (!str_contains($hookSuffix, 'my-plugin')) {
            return;
        }

        wp_enqueue_style(
            'my-plugin-admin',
            plugins_url('assets/css/admin.css', $this->pluginDirectory . '/plugin.php'),
            [],
            filemtime($this->pluginDirectory . '/assets/css/admin.css')
        );

        wp_enqueue_script(
            'my-plugin-admin',
            plugins_url('assets/js/admin.js', $this->pluginDirectory . '/plugin.php'),
            ['jquery'],
            filemtime($this->pluginDirectory . '/assets/js/admin.js'),
            true
        );

        wp_localize_script('my-plugin-admin', 'myPluginAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('my_plugin_ajax'),
        ]);
    }
}
```

The `$pluginDirectory` parameter is injected automatically by the kernel via the `%pluginDirectory%` binding.
