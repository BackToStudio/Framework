<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose;

use BackTo\Framework\Contracts\ExtensionInterface;

/**
 * Base class for framework module extensions.
 *
 * Provides a default {@see getBundles()} implementation that wraps
 * the single bundle from {@see getBundle()} into an array.
 *
 * Extensions with multiple bundles (e.g., SecurityExtension) should
 * override {@see getBundles()} directly.
 *
 * This eliminates the need for `method_exists()` checks in the kernel,
 * respecting the Open/Closed Principle.
 */
abstract class AbstractExtension implements ExtensionInterface
{
    /**
     * @return array<int, array{dir: string, namespace: string, exclude: string}>
     */
    public function getBundles(): array
    {
        $bundle = $this->getBundle();

        if ($bundle === null) {
            return [];
        }

        return [$bundle];
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
