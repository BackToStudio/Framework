<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\DependencyInjection\Compiler;

use BackTo\Framework\Bundle\Security\Network\ServerConfigGenerator;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Reads security.bot_protection.* parameters and wires them
 * into the ServerConfigGenerator service via setter calls.
 */
final class ConfigureServerConfigGeneratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ServerConfigGenerator::class)) {
            return;
        }

        $definition = $container->getDefinition(ServerConfigGenerator::class);

        $simpleMapping = [
            'security.bot_protection.blocked_user_agents' => 'setBlockedUserAgents',
            'security.bot_protection.blocked_ips' => 'setBlockedIps',
            'security.bot_protection.sensitive_endpoints' => 'setSensitiveEndpoints',
            'security.bot_protection.max_connections_per_ip' => 'setMaxConnectionsPerIp',
            'security.bot_protection.block_empty_user_agent' => 'setBlockEmptyUserAgent',
        ];

        foreach ($simpleMapping as $parameter => $method) {
            if ($container->hasParameter($parameter)) {
                $definition->addMethodCall($method, [$container->getParameter($parameter)]);
            }
        }

        // Rate limits use paired parameters (rate + burst)
        if ($container->hasParameter('security.bot_protection.global_rate_limit')
            && $container->hasParameter('security.bot_protection.global_burst')
        ) {
            $definition->addMethodCall('setGlobalRateLimit', [
                $container->getParameter('security.bot_protection.global_rate_limit'),
                $container->getParameter('security.bot_protection.global_burst'),
            ]);
        }

        if ($container->hasParameter('security.bot_protection.sensitive_rate_limit')
            && $container->hasParameter('security.bot_protection.sensitive_burst')
        ) {
            $definition->addMethodCall('setSensitiveRateLimit', [
                $container->getParameter('security.bot_protection.sensitive_rate_limit'),
                $container->getParameter('security.bot_protection.sensitive_burst'),
            ]);
        }
    }
}
