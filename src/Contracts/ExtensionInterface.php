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
     * Return all bundle configurations for service auto-discovery.
     *
     * Most extensions have a single bundle; the default implementation
     * in {@see AbstractExtension} wraps {@see getBundle()} in an array.
     * Override this method to register multiple bundles (e.g., Security + TwoFactor).
     *
     * @return array<int, array{dir: string, namespace: string, exclude: string}>
     */
    public function getBundles(): array;

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
