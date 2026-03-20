# Getting started with the Plugin Bundle

*Tutorial — Learning-oriented*

This tutorial walks you through bootstrapping a WordPress plugin with `PluginKernel`. By the end, your plugin will have a working DI container and automatic translation loading.

## Prerequisites

- A WordPress installation
- Composer autoloading configured

## Step 1: Create the plugin entry file

Create your main plugin file with the standard WordPress header and initialize the kernel:

```php
<?php
/**
 * Plugin Name: My Plugin
 * Text Domain: my-plugin
 */

declare(strict_types=1);

use BackTo\Framework\Bundle\Plugin\PluginKernel;

$kernel = new PluginKernel(
    environment: 'production',
    debug: false,
);

$kernel->setProjectDir(__DIR__);
$kernel->setTextDomain('my-plugin');
```

## Step 2: Add extensions

Register any framework bundles your plugin needs before booting:

```php
<?php

use BackTo\Framework\Bundle\Admin\AdminExtension;
use BackTo\Framework\Bundle\Blocks\BlocksExtension;

$kernel->addExtension(new AdminExtension());
$kernel->addExtension(new BlocksExtension());
$kernel->boot();
```

## Step 3: Create the languages directory

Create a `languages/` folder at the root of your plugin. Place your `.po` and `.mo` files there.

The `LoadPluginTextDomain` service loads translations automatically on the `init` hook.

## Step 4: Verify

Activate the plugin in WordPress. The DI container compiles, services are registered, and translations are loaded.

## Next steps

- See [Common tasks](how-to/README.md) for mu-plugin support, environment configuration, and custom services
- See [Architecture](explanation.md) to understand the kernel lifecycle
