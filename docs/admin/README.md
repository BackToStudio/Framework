# Admin Bundle

An abstraction layer for registering WordPress admin pages, menus, and role capabilities through the DI container.

## Overview

The Admin bundle auto-discovers classes implementing `AdminPageInterface`, collects them via a compiler pass, and registers them on the `admin_menu` hook. It also provides ready-made actions for granting editors access to the Appearance menu and adding a Reusable Blocks menu.

```php
<?php

use BackTo\Framework\Bundle\Admin\AdminExtension;

$kernel->addExtension(new AdminExtension());
```

## Documentation

| Document | Type | Description |
|---|---|---|
| [Getting started](tutorial.md) | Tutorial | Create and register your first admin page |
| [Common tasks](how-to.md) | How-to | Submenus, editor access, reusable blocks menu |
| [API reference](reference.md) | Reference | Interfaces, classes, compiler passes |
| [Architecture](explanation.md) | Explanation | Hexagonal design, auto-configuration, registry pattern |
