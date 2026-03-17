<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;

/**
 * Fluent configurator for Assets module parameters.
 *
 * Used in config/assets.php to override defaults without
 * knowing the underlying parameter key names:
 *
 *     return static function (AssetsConfigurator $assets): void {
 *         $assets->versionStrategy('timestamp');
 *     };
 */
class AssetsConfigurator implements ModuleConfiguratorInterface
{
    /** @var array<string, mixed> */
    private array $overrides = [];

    public function versionStrategy(string $strategy): self
    {
        $this->overrides['assets.version_strategy'] = $strategy;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toParameters(): array
    {
        return $this->overrides;
    }
}
