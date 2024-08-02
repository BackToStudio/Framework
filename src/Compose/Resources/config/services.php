<?php

use BackToVendor\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
                             ->defaults()
                             ->autowire()       // Automatically injects dependencies in your services.
                             ->autoconfigure(); // Automatically registers your services as commands, event subscribers, etc.

    $services->load('BackTo\\Framework\\Assets\\', '../../../Assets/*')
             ->exclude('../../../Assets/{DependencyInjection,Entity,Tests,Contracts}');
    $services->load('BackTo\\Framework\\Hooks\\', '../../../Hooks/*')
             ->exclude('../../../Hooks/{DependencyInjection,Entity,Tests,Contracts}');
    $services->load('BackTo\\Framework\\Blocks\\', '../../../Blocks/*')
             ->exclude('../../../Blocks/{DependencyInjection,Entity,Tests,Contracts}');
    $services->load('BackTo\\Framework\\PostType\\', '../../../PostType/*')
             ->exclude('../../../PostType/{DependencyInjection,Entity,Tests,Contracts}');
    $services->load('BackTo\\Framework\\Taxonomy\\', '../../../Taxonomy/*')
             ->exclude('../../../Taxonomy/{DependencyInjection,Entity,Tests,Contracts}');
};
