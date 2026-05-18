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
final class AssetsConfigurator implements ModuleConfiguratorInterface
{
    /** @var array<string, mixed> */
    private array $overrides = [];

    private const ALLOWED_VERSION_STRATEGIES = ['content_hash', 'timestamp', 'version'];

    public function versionStrategy(string $strategy): self
    {
        if (!in_array($strategy, self::ALLOWED_VERSION_STRATEGIES, true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid version strategy "%s". Allowed: %s.', $strategy, implode(', ', self::ALLOWED_VERSION_STRATEGIES)));
        }

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
