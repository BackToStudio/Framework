# Tutorial: Build a WordPress Plugin with BackTo Framework

This tutorial walks you through creating a WordPress plugin powered by BackTo Framework. By the end, you will have a working plugin with activation/deactivation hooks and a registered custom post type.

## Prerequisites

- PHP 8.1 or higher
- Composer installed
- A running WordPress installation

## 1. Create your plugin directory

In your WordPress `wp-content/plugins/` directory:

```bash
mkdir my-plugin && cd my-plugin
```

## 2. Install the framework

```bash
composer init --name=my-vendor/my-plugin --require=backto/framework:*
composer install
```

## 3. Create the main plugin file

Create `my-plugin.php`:

```php
<?php
/*
Plugin Name: My Plugin
Description: A plugin built with BackTo Framework
Version: 1.0.0
Text Domain: my-plugin
*/

require_once __DIR__ . '/vendor/autoload.php';

$kernel = new MyPlugin\Kernel('production', false);
$kernel->setTextDomain('my-plugin');
$kernel->load();
```

## 4. Create the Kernel

Create `src/Kernel.php`:

```php
<?php

namespace MyPlugin;

use BackTo\Framework\Plugin\PluginKernel;

class Kernel extends PluginKernel
{
}
```

`PluginKernel` provides the same container management as `ThemeKernel`, with plugin-specific defaults (directory and text domain parameter names).

## 5. Create the service configuration

Create `config/services.php`:

```php
<?php

use BackToVendor\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('MyPlugin\\', '../src/*')
        ->exclude('../src/{Entity,Tests}');
};
```

## 6. Create a custom post type with activation hook

Create `src/PostType/Event.php`:

```php
<?php

namespace MyPlugin\PostType;

use BackTo\Framework\PostType\Contracts\PostTypeInterface;

class Event implements PostTypeInterface
{
    public function getKey(): string
    {
        return 'event';
    }

    public function getArgs(): array
    {
        return [
            'label' => 'Events',
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        ];
    }
}
```

## 7. Activate and test

1. Go to **Plugins** in the WordPress admin
2. Activate **My Plugin**
3. You should see a new "Events" menu item in the sidebar

## Differences from a theme

| Aspect | ThemeKernel | PluginKernel |
|--------|-------------|--------------|
| Directory parameter | `%themeDirectory%` | `%pluginDirectory%` |
| Text domain parameter | `%themeTextDomain%` | `%pluginTextDomain%` |
| I18n | `load_theme_textdomain` | `load_plugin_textdomain` |
| Activation hooks | Not applicable | Supported via `ActivationHooks` interface |
| Deactivation hooks | Not applicable | Supported via `DeactivationHooks` interface |

## Next steps

- [Register custom post types](../how-to/register-post-types.md) with advanced options
- [Use the cache system](../how-to/use-cache.md) for performance
- Read the [Architecture overview](../reference/architecture.md)
