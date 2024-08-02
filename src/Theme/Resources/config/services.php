<?php

use BackToVendor\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
	$services = $configurator->services()
	                         ->defaults()
							 ->bind('$themeDirectory', '%themeDirectory%')
							 ->bind('$themeTextDomain', '%themeTextDomain%')
	                         ->autowire()       // Automatically injects dependencies in your services.
	                         ->autoconfigure(); // Automatically registers your services as commands, event subscribers, etc.

	$services->load('BackTo\\Framework\\Theme\\I18n\\', 'I18n/*');
};
