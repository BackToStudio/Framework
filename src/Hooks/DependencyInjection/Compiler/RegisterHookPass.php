<?php

namespace BackTo\Framework\Hooks\DependencyInjection\Compiler;

use BackTo\Framework\Hooks\HookRegistry;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

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
