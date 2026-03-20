# Register a top-level menu page manually

Use `AdminPageRegistrarInterface::registerMenuPage()`:

```php
<?php

use BackTo\Framework\Bundle\Admin\Contracts\AdminPageRegistrarInterface;

$this->registrar->registerMenuPage([
    'page_title' => 'Analytics',
    'menu_title' => 'Analytics',
    'capability' => 'manage_options',
    'menu_slug'  => 'my-analytics',
    'callback'   => [$this, 'render'],
    'icon_url'   => 'dashicons-chart-area',
    'position'   => 25,
]);
```
