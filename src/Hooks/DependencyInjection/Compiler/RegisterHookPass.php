<?php

namespace Fantassin\Core\WordPress\Hooks\DependencyInjection\Compiler;

use Fantassin\Core\WordPress\Hooks\HookRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterHookPass implements CompilerPassInterface
{
	public function process(ContainerBuilder $container)
	{
		if ( ! $container->hasDefinition( HookRegistry::class ) ) {
			return;
		}

		$registryDefinition = $container->findDefinition( HookRegistry::class );

		foreach ($container->findTaggedServiceIds('wordpress.hooks') as $id => $tags) {
			$registryDefinition->addMethodCall( 'addHook', [new Reference($id)] );
		}
	}
}
