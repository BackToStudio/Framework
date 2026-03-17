<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

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
