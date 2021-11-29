<?php

namespace Fantassin\Core\WordPress\Hooks\DependencyInjection\Compiler;

use Fantassin\Core\WordPress\Hooks\HookRegistry;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Reference;

class RegisterHookPass implements CompilerPassInterface
{
	public function process(ContainerBuilder $container)
	{
		if ( ! $container->hasDefinition( HookRegistry::class ) ) {
			return;
		}

		$registryDefinition = $container->findDefinition( HookRegistry::class );

		foreach ($container->findTaggedServiceIds('wordpress.hook') as $id => $tags) {
			$registryDefinition->addMethodCall( 'addHook', [new Reference($id)] );
		}
	}
}
