<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\DependencyInjection\Compiler;

use BackTo\Framework\Bundle\Security\Hardening\AutoUpdatePolicy;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Reads security.auto_update_* parameters and wires them
 * into the AutoUpdatePolicy service via setter calls.
 */
final class ConfigureAutoUpdatePolicyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(AutoUpdatePolicy::class)) {
            return;
        }

        $definition = $container->getDefinition(AutoUpdatePolicy::class);

        $mapping = [
            'security.auto_update_major_core' => 'setMajorCore',
            'security.auto_update_minor_core' => 'setMinorCore',
            'security.auto_update_plugins' => 'setPlugins',
            'security.auto_update_themes' => 'setThemes',
            'security.auto_update_translations' => 'setTranslations',
            'security.auto_update_allowed_plugins' => 'setAllowedPlugins',
            'security.auto_update_allowed_themes' => 'setAllowedThemes',
        ];

        foreach ($mapping as $parameter => $method) {
            if ($container->hasParameter($parameter)) {
                $definition->addMethodCall($method, [$container->getParameter($parameter)]);
            }
        }
    }
}
