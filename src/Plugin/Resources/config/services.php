<?php

use BackToVendor\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use BackTo\Framework\Contracts\ThemeInterface;
use BackTo\Framework\Theme\Entity\Theme;

return function (ContainerConfigurator $configurator) {
	$services = $configurator->services()
	                         ->defaults()
							 ->bind('$pluginDirectory', '%pluginDirectory%')
							 ->bind('$pluginTextDomain', '%pluginTextDomain%')
	                         ->autowire()       // Automatically injects dependencies in your services.
	                         ->autoconfigure(
		); // Automatically registers your services as commands, event subscribers, etc.

	$services->load('BackTo\\Framework\\Plugin\\I18n\\', 'I18n/*');
};
