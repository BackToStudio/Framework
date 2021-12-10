<?php

use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
                             ->defaults()
                             ->autowire()       // Automatically injects dependencies in your services.
                             ->autoconfigure(
        ); // Automatically registers your services as commands, event subscribers, etc.

    $services->load('Fantassin\\Core\\WordPress\\Hooks\\', '../../../Hooks/*')
             ->exclude('../../../Hooks/{DependencyInjection,Entity,Tests,Contracts}');
    $services->load('Fantassin\\Core\\WordPress\\Blocks\\', '../../../Blocks/*')
             ->exclude('../../../Blocks/{DependencyInjection,Entity,Tests,Contracts}');
    $services->load('Fantassin\\Core\\WordPress\\PostType\\', '../../../PostType/*')
             ->exclude('../../../PostType/{DependencyInjection,Entity,Tests,Contracts}');
    $services->load('Fantassin\\Core\\WordPress\\Taxonomy\\', '../../../Taxonomy/*')
             ->exclude('../../../Taxonomy/{DependencyInjection,Entity,Tests,Contracts}');
};
