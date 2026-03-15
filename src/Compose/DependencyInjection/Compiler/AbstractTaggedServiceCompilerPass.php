<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose\DependencyInjection\Compiler;

use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

abstract class AbstractTaggedServiceCompilerPass implements CompilerPassInterface
{
    abstract protected function getRegistryClass(): string;

    abstract protected function getTag(): string;

    protected function getRegistryMethod(): string
    {
        return 'add';
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition($this->getRegistryClass())) {
            return;
        }

        $registryDefinition = $container->findDefinition($this->getRegistryClass());

        foreach ($container->findTaggedServiceIds($this->getTag()) as $id => $tags) {
            $registryDefinition->addMethodCall($this->getRegistryMethod(), [new Reference($id)]);
        }
    }
}
