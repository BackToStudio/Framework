# Register a submenu page

Use `AdminPageRegistrarInterface::registerSubmenuPage()`:

```php
<?php

use BackTo\Framework\Bundle\Admin\Contracts\AdminPageRegistrarInterface;

$this->registrar->registerSubmenuPage('my-parent-slug', [
    'page_title' => 'Settings',
    'menu_title' => 'Settings',
    'capability' => 'manage_options',
    'menu_slug'  => 'my-settings',
    'callback'   => [$this, 'render'],
]);
```
