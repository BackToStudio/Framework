<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Default parameter values for the Security module.
 *
 * Override any parameter in your project's config/security.php:
 *
 *     use BackToVendor\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
 *
 *     return static function (ContainerConfigurator $container): void {
 *         $container->parameters()
 *             ->set('security.password_min_length', 16)
 *             ->set('security.two_factor_enabled', true);
 *     };
 */
final class SecurityConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'security.headers_enabled' => true,
            'security.xmlrpc_disabled' => true,
            'security.hide_version' => true,
            'security.csp_report_only' => false,
            'security.password_min_length' => 12,
            'security.max_concurrent_sessions' => 1,
            'security.rest_api_require_auth' => true,
            'security.disable_file_editor' => true,
            'security.two_factor_enabled' => false,
            'security.two_factor_issuer' => 'WordPress',
            'security.auto_update_major_core' => false,
            'security.auto_update_minor_core' => true,
            'security.auto_update_plugins' => false,
            'security.auto_update_themes' => false,
            'security.auto_update_translations' => true,
            'security.auto_update_allowed_plugins' => [],
            'security.auto_update_allowed_themes' => [],

            // Bot protection — server config generator
            'security.bot_protection.blocked_user_agents' => [
                'SemrushBot',
                'AhrefsBot',
                'DotBot',
                'MJ12bot',
                'BLEXBot',
                'PetalBot',
                'DataForSeoBot',
                'GPTBot',
                'CCBot',
            ],
            'security.bot_protection.blocked_ips' => [],
            'security.bot_protection.sensitive_endpoints' => [
                'wp-login.php',
                'xmlrpc.php',
                'wp-cron.php',
            ],
            'security.bot_protection.global_rate_limit' => 10,
            'security.bot_protection.global_burst' => 20,
            'security.bot_protection.sensitive_rate_limit' => 2,
            'security.bot_protection.sensitive_burst' => 3,
            'security.bot_protection.max_connections_per_ip' => 20,
            'security.bot_protection.block_empty_user_agent' => true,
        ];
    }

    public static function apply(ContainerBuilder $containerBuilder): void
    {
        foreach (self::getDefaults() as $key => $value) {
            if (!$containerBuilder->hasParameter($key)) {
                $containerBuilder->setParameter($key, $value);
            }
        }
    }
}
