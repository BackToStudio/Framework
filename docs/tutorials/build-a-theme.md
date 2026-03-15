# Tutorial: Build a WordPress Theme with BackTo Framework

This tutorial walks you through creating a WordPress theme powered by BackTo Framework. By the end, you will have a working theme with a custom post type, a taxonomy, and a service container managing your dependencies.

## Prerequisites

- PHP 8.1 or higher
- Composer installed
- A running WordPress installation

## 1. Create your theme directory

In your WordPress `wp-content/themes/` directory, create a new theme:

```bash
mkdir my-theme && cd my-theme
```

Create the required `style.css`:

```css
/*
Theme Name: My Theme
Description: A theme built with BackTo Framework
Version: 1.0.0
Text Domain: my-theme
*/
```

## 2. Install the framework

```bash
composer require backto/framework
```

## 3. Create the Kernel

The Kernel is the entry point of your application. It boots the Symfony DI container and loads all your services.

Create `src/Kernel.php`:

```php
<?php

namespace MyTheme;

use BackTo\Framework\Theme\ThemeKernel;

class Kernel extends ThemeKernel
{
}
```

That's it. `ThemeKernel` provides everything you need: container building, caching, service loading, and text domain support.

## 4. Create the service configuration

Create the file `config/services.php`. This is where you register your own services:

```php
<?php

use BackToVendor\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Register all classes in your src/ directory as services.
    $services->load('MyTheme\\', '../src/*')
        ->exclude('../src/{Entity,Tests}');
};
```

## 5. Create a custom post type

Create `src/PostType/Portfolio.php`:

```php
<?php

namespace MyTheme\PostType;

use BackTo\Framework\PostType\Contracts\PostTypeInterface;

class Portfolio implements PostTypeInterface
{
    public function getKey(): string
    {
        return 'portfolio';
    }

    public function getArgs(): array
    {
        return [
            'label' => 'Portfolio',
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
        ];
    }
}
```

Because `Portfolio` implements `PostTypeInterface`, the framework will automatically discover it, tag it, and register it with WordPress. No manual wiring needed.

## 6. Boot the Kernel in functions.php

Create `functions.php`:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$kernel = new MyTheme\Kernel('production', false);
$kernel->setTextDomain('my-theme');
$kernel->load();
```

## 7. Verify it works

1. Activate your theme in the WordPress admin
2. Go to the admin dashboard
3. You should see a new "Portfolio" menu item in the sidebar

## What happened?

When you called `$kernel->load()`, the framework:

1. Built a Symfony DI container from your `config/services.php`
2. Auto-discovered your `Portfolio` class (tagged via `PostTypeInterface`)
3. Used a compiler pass to collect all tagged post types into the `PostTypeRegistry`
4. The `RegisterPostType` service hooked into WordPress `init` and registered your post type

## Next steps

- [Register a custom taxonomy](../how-to/register-taxonomies.md) to categorize your portfolio items
- [Register block styles](../how-to/register-block-styles.md) for your theme
- Read the [Architecture overview](../reference/architecture.md) to understand how the pieces fit together
