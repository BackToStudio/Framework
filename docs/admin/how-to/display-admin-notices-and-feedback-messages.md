# Display admin notices and feedback messages

Hook into `admin_notices` to display contextual feedback:

```php
<?php

namespace MyPlugin\Admin;

use BackTo\Framework\Contracts\HookDispatcherInterface;

final class AdminNotices
{
    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
    ) {}

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_notices', [$this, 'showDependencyWarning']);
    }

    public function showDependencyWarning(): void
    {
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            return;
        }

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>My Plugin:</strong> WooCommerce is required for full functionality.</p>';
        echo '</div>';
    }
}
```

Use `notice-success`, `notice-error`, `notice-warning`, or `notice-info` classes. Add `is-dismissible` for a close button.
