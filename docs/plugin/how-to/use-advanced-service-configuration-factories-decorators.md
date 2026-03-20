# Use advanced service configuration (factories, decorators)


Register services with factory methods or decorators for complex wiring:

```php
<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Service with factory
    $services->set(\MyPlugin\Api\ApiClient::class)
        ->factory([\MyPlugin\Api\ApiClientFactory::class, 'create'])
        ->args([
            '%my_plugin.api_key%',
            '%my_plugin.api_url%',
        ]);

    // Decorate an existing service
    $services->set(\MyPlugin\Cache\CachedProductRepository::class)
        ->decorate(\MyPlugin\Contracts\ProductRepositoryInterface::class)
        ->args([service('.inner')]);

    // Service with method calls
    $services->set(\MyPlugin\Notifications\NotificationManager::class)
        ->call('addChannel', [service(\MyPlugin\Notifications\EmailChannel::class)])
        ->call('addChannel', [service(\MyPlugin\Notifications\SlackChannel::class)]);
};
```
