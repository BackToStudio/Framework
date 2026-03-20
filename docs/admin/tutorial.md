# Getting started with the Admin Bundle

*Tutorial — Learning-oriented*

This tutorial walks you through creating a custom WordPress admin page using the framework. By the end, your page will appear in the WordPress admin menu and render content.

## Prerequisites

- A WordPress plugin or theme using the BackTo Framework
- The framework's DI container configured

## Step 1: Register the extension

Add `AdminExtension` to your kernel before booting:

```php
<?php

use BackTo\Framework\Bundle\Admin\AdminExtension;

$kernel->addExtension(new AdminExtension());
$kernel->boot();
```

## Step 2: Create an admin page class

Create a class implementing `AdminPageInterface`. Each method defines one aspect of the menu entry:

```php
<?php

declare(strict_types=1);

namespace App\Admin;

use BackTo\Framework\Bundle\Admin\Contracts\AdminPageInterface;

final class DashboardPage implements AdminPageInterface
{
    public function getPageTitle(): string
    {
        return 'My Dashboard';
    }

    public function getMenuTitle(): string
    {
        return 'Dashboard';
    }

    public function getCapability(): string
    {
        return 'manage_options';
    }

    public function getMenuSlug(): string
    {
        return 'my-dashboard';
    }

    public function getIconUrl(): string
    {
        return 'dashicons-dashboard';
    }

    public function getPosition(): ?int
    {
        return 2;
    }

    public function render(): void
    {
        echo '<div class="wrap"><h1>My Dashboard</h1></div>';
    }

    public function hooks(): void
    {
        // No additional hooks needed.
    }
}
```

## Step 3: Verify the result

The framework auto-detects your class via the `wordpress.admin_page` tag. The `RegisterAdminPagePass` compiler pass collects it into the `AdminPageRegistry`, and `RegisterAdminPage` registers it on the `admin_menu` hook.

Visit your WordPress admin — the "Dashboard" menu item appears at position 2.

## Next steps

- See [Common tasks](how-to.md) for submenus, editor access, and reusable blocks
- See [API reference](reference.md) for the complete interface documentation
- See [Architecture](explanation.md) to understand the registry and ports & adapters design
