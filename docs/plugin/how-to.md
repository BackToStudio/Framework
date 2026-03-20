# Plugin Bundle — How-to guides

*How-to — Task-oriented*

Practical recipes for common plugin configuration tasks.

---

## Load translations for a mu-plugin

Use `LoadMuPluginTextDomain` instead of `LoadPluginTextDomain`. It calls `load_muplugin_textdomain()`, which looks for translations in `mu-plugins/my-plugin/languages/`.

Ensure your service configuration loads the `I18n` namespace:

```php
<?php

$services->load('MyPlugin\\I18n\\', 'I18n/*');
```

---

## Configure the environment dynamically

Pass WordPress environment functions to the kernel constructor:

```php
<?php

use BackTo\Framework\Bundle\Plugin\PluginKernel;

$kernel = new PluginKernel(
    environment: wp_get_environment_type(),
    debug: WP_DEBUG,
);
```

Setting `debug: true` disables the DI container cache, useful during development.

---

## Add plugin-specific services

Create a `Resources/config/services.php` file in your plugin:

```php
<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->bind('$pluginDirectory', '%pluginDirectory%')
        ->bind('$pluginTextDomain', '%pluginTextDomain%')
        ->autowire()
        ->autoconfigure();

    $services->load('MyPlugin\\', '../src/*');
};
```

The `%pluginDirectory%` and `%pluginTextDomain%` parameters are injected automatically by the kernel.
