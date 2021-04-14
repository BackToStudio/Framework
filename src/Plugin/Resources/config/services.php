<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()       // Automatically injects dependencies in your services.
        ->autoconfigure(); // Automatically registers your services as commands, event subscribers, etc.

    // Makes classes in includes/ available to be used as services.
    // This creates a service per class whose id is the fully-qualified class name.
    $services->load('Fantassin\\Core\\WordPress\\Hooks\\', '../Hooks/*')
        ->exclude('../Hooks/{DependencyInjection,Entity}');
    $services->load('Fantassin\\Core\\WordPress\\Blocks\\', '../Blocks/*')
        ->exclude('../Blocks/{DependencyInjection,Entity}');
    $services->load('Fantassin\\Core\\WordPress\\PostType\\', '../PostType/*')
        ->exclude('../PostType/{DependencyInjection,Entity}');

};
