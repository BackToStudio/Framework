# Configuration Reference

## Project structure

```
my-project/
├── config/
│   └── services.php          # Project service definitions
├── src/
│   ├── Kernel.php            # Extends ThemeKernel or PluginKernel
│   ├── PostType/             # Custom post types
│   ├── Taxonomy/             # Custom taxonomies
│   └── ...
├── var/                      # Generated container cache
│   ├── container.php
│   └── container.meta.php
├── composer.json
└── functions.php             # (theme) or plugin.php (plugin)
```

## config/services.php

The main service configuration file. Must return a closure receiving a `ContainerConfigurator`:

```php
<?php

use BackToVendor\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('MyApp\\', '../src/*')
        ->exclude('../src/{Entity,Tests}');
};
```

## Kernel instantiation

### Theme

```php
$kernel = new MyTheme\Kernel('production', false);
$kernel->setTextDomain('my-theme');
$kernel->load();
```

### Plugin

```php
$kernel = new MyPlugin\Kernel('production', false);
$kernel->setTextDomain('my-plugin');
$kernel->load();
```

### Constructor parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$environment` | `string` | Environment name (e.g. `'production'`, `'development'`) |
| `$debug` | `bool` | `true` to rebuild the container on every request |

## Debug mode

When `$debug` is `true`:
- The DI container is rebuilt on every request (no cache)
- Exceptions are thrown instead of silently caught

When `$debug` is `false`:
- The container is cached in `var/container.php`
- Exceptions are caught and the framework fails silently

## Available DI parameters

### ThemeKernel

| Parameter | Value |
|-----------|-------|
| `%themeDirectory%` | Project root directory (auto-detected) |
| `%themeTextDomain%` | Text domain set via `setTextDomain()` |

### PluginKernel

| Parameter | Value |
|-----------|-------|
| `%pluginDirectory%` | Project root directory (auto-detected) |
| `%pluginTextDomain%` | Text domain set via `setTextDomain()` |

## Binding parameters in services.php

Use `bind()` to inject scalar parameters:

```php
$services = $configurator->services()
    ->defaults()
    ->bind('$themeDirectory', '%themeDirectory%')
    ->bind('$themeTextDomain', '%themeTextDomain%')
    ->autowire()
    ->autoconfigure();
```

## Container cache

The compiled container is stored in `var/container.php`. To clear it:

```bash
rm -f var/container.php var/container.meta.php
```

This happens automatically on `composer dump-autoload` (via the `post-autoload-dump` script).

## PHPStan configuration

```yaml
# phpstan.neon
includes:
    - vendor/szepeviktor/phpstan-wordpress/extension.neon
    - phpstan-baseline.neon
parameters:
    level: 6
    paths: [src]
    excludePaths: [src/*/Tests/*]
```

## PHPUnit configuration

Test suites are defined per module in `phpunit.xml`:

```xml
<testsuite name="post-type">
    <directory>src/PostType/Tests</directory>
</testsuite>
```

Run all tests: `vendor/bin/phpunit`
Run a single suite: `vendor/bin/phpunit --testsuite post-type`
