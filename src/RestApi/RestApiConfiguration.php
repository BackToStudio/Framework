<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi;

use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Default parameter values for the REST API module.
 *
 * Override any parameter in your project's config/rest-api.php:
 *
 *     use BackTo\Framework\RestApi\RestApiConfigurator;
 *
 *     return static function (RestApiConfigurator $restApi): void {
 *         $restApi
 *             ->defaultNamespace('custom/v2')
 *             ->defaultPerPage(25);
 *     };
 */
final class RestApiConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'rest_api.default_namespace' => 'app/v1',
            'rest_api.default_per_page' => 10,
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
