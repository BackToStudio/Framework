<?php

use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Fantassin\Core\WordPress\Contracts\ThemeInterface;
use Fantassin\Core\WordPress\Theme\Entity\Theme;

return function (ContainerConfigurator $configurator) {
	$services = $configurator->services()
	                         ->defaults()
							 ->bind('$pluginDirectory', '%pluginDirectory%')
							 ->bind('$pluginTextDomain', '%pluginTextDomain%')
	                         ->autowire()       // Automatically injects dependencies in your services.
	                         ->autoconfigure(
		); // Automatically registers your services as commands, event subscribers, etc.

	$services->load('Fantassin\\Core\\WordPress\\Plugin\\I18n\\', 'I18n/*');
};
