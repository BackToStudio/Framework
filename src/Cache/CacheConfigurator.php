<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;

/**
 * Fluent configurator for Cache module parameters.
 *
 * Used in config/cache.php to override defaults without
 * knowing the underlying parameter key names:
 *
 *     return static function (CacheConfigurator $cache): void {
 *         $cache
 *             ->ttl(7200)
 *             ->enabled(false);
 *     };
 */
class CacheConfigurator implements ModuleConfiguratorInterface
{
    /** @var array<string, mixed> */
    private array $overrides = [];

    public function ttl(int $seconds): self
    {
        $this->overrides['cache.ttl'] = $seconds;

        return $this;
    }

    public function enabled(bool $enabled): self
    {
        $this->overrides['cache.enabled'] = $enabled;

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
