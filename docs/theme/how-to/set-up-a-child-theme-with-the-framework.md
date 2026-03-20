# Set up a child theme with the framework

Create a child theme kernel that extends the parent theme's configuration:

```php
<?php
// child-theme/functions.php

use BackTo\Framework\Bundle\Theme\ThemeKernel;

$kernel = new ThemeKernel(
    environment: wp_get_environment_type(),
    debug: WP_DEBUG,
);

$kernel->boot(
    directory: get_stylesheet_directory(),
    textDomain: 'my-child-theme',
);
```

In your child theme's `Resources/config/services.php`, load only the services you want to add or override:

```php
<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->bind('$themeDirectory', '%themeDirectory%')
        ->bind('$themeTextDomain', '%themeTextDomain%')
        ->autowire()
        ->autoconfigure();

    // Load only child-theme-specific services
    $services->load('MyChildTheme\\', '../src/*');
};
```

The parent theme bundles are inherited automatically. The child theme can override any service by registering one with the same interface.
