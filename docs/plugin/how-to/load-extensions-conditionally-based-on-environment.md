# Load extensions conditionally based on environment

Use the environment parameter to load different service configurations:

```php
<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Always loaded
    $services->load('MyPlugin\\Core\\', '../src/Core/*');

    // Load debug services only in development
    if ($configurator->env() === 'development' || $configurator->env() === 'local') {
        $services->load('MyPlugin\\Debug\\', '../src/Debug/*');
    }

    // Load admin services only when in admin context
    if (is_admin()) {
        $services->load('MyPlugin\\Admin\\', '../src/Admin/*');
    }
};
```

The `environment` value comes from `wp_get_environment_type()` and can be `local`, `development`, `staging`, or `production`.
