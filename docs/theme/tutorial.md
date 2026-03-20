# Getting started with the Theme Bundle

*Tutorial — Learning-oriented*

This tutorial walks you through bootstrapping a WordPress theme with `ThemeKernel`. By the end, your theme will have a working DI container, automatic translations, and a clean HTML `<head>`.

## Prerequisites

- A WordPress installation
- Composer autoloading configured

## Step 1: Initialize the kernel in functions.php

In your theme's `functions.php`, create and boot the kernel:

```php
<?php

declare(strict_types=1);

use BackTo\Framework\Bundle\Theme\ThemeKernel;

$kernel = new ThemeKernel(
    environment: wp_get_environment_type(),
    debug: WP_DEBUG,
);

$kernel->setProjectDir(get_template_directory());
$kernel->setTextDomain('my-theme');
```

## Step 2: Add extensions

Register any framework bundles your theme needs before booting:

```php
<?php

use BackTo\Framework\Bundle\Blocks\BlocksExtension;
use BackTo\Framework\Bundle\Admin\AdminExtension;

$kernel->addExtension(new BlocksExtension());
$kernel->addExtension(new AdminExtension());
$kernel->boot();
```

## Step 3: Create the languages directory

Create a `languages/` folder at the root of your theme for `.po` and `.mo` files. The `LoadThemeTextDomain` service loads translations automatically on the `after_setup_theme` hook.

## Step 4: Verify

Activate the theme in WordPress. The cleanup actions (`CleanHead`, `RemoveEmojis`, `RemoveWordPressVersion`, `RemoveSvgFilters`, `RemoveNavigationFallback`) are enabled automatically. View the page source to confirm the `<head>` is clean.

## Next steps

- See [Common tasks](how-to.md) for disabling specific cleanup actions
- See [API reference](reference.md) for the complete class documentation
