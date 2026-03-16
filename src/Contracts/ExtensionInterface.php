<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Interface for framework module extensions.
 *
 * Each module that needs to register services, compiler passes,
 * autoconfiguration rules, or port bindings in the DI container
 * implements this interface.
 *
 * Usage in a plugin kernel:
 *
 *     protected function getExtensions(): array
 *     {
 *         return [
 *             new HooksExtension(),
 *             new PostTypeExtension(),
 *             new SecurityExtension(),
 *         ];
 *     }
 */
interface ExtensionInterface
{
    /**
     * Return the bundle configuration for service auto-discovery.
     *
     * @return array{dir: string, namespace: string, exclude: string}|null
     */
    public function getBundle(): ?array;

    /**
     * Register services, compiler passes, autoconfiguration, and port bindings.
     */
    public function register(ContainerBuilder $containerBuilder): void;

    /**
     * Return default parameter values for this module.
     *
     * @return array<string, mixed>
     */
    public function getDefaultConfiguration(): array;
}
