<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;

/**
 * Fluent configurator for Observability module parameters.
 *
 * Used in config/observability.php to override defaults without
 * knowing the underlying parameter key names:
 *
 *     return static function (ObservabilityConfigurator $observability): void {
 *         $observability
 *             ->logLevel('debug')
 *             ->performanceTracking(true);
 *     };
 */
final class ObservabilityConfigurator implements ModuleConfiguratorInterface
{
    /** @var array<string, mixed> */
    private array $overrides = [];

    public function logLevel(string $level): self
    {
        $this->overrides['observability.log_level'] = $level;

        return $this;
    }

    public function performanceTracking(bool $enabled): self
    {
        $this->overrides['observability.performance_tracking'] = $enabled;

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
