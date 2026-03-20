# Admin Bundle — How-to guides

*How-to — Task-oriented*

Practical recipes for common admin page tasks.

---

## Register a submenu page

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

---

## Add a Reusable Blocks menu

Enable the `AddReusableBlockMenu` class in your service configuration. It registers a top-level menu pointing to `edit.php?post_type=wp_block` with the `dashicons-block-default` icon at position 30.

No code is needed beyond making the class available as a service — it hooks itself automatically.

---

## Grant editors access to the Appearance menu

Enable `AddMenuForEditors` in your service configuration. It:

- Adds the `edit_theme_options` capability to the `editor` role.
- Hides irrelevant submenus (themes, widgets, customizer) from editors.
- Removes the Customizer link from the admin bar.

---

## Register a top-level menu page manually

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
