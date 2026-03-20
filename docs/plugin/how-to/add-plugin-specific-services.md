# Add plugin-specific services

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
