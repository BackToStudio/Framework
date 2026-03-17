<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;

/**
 * Fluent configurator for SEO module parameters.
 *
 * Used in config/seo.php to override defaults without
 * knowing the underlying parameter key names:
 *
 *     return static function (SeoConfigurator $seo): void {
 *         $seo
 *             ->titleSeparator('-')
 *             ->robotsDefault('noindex, nofollow');
 *     };
 */
class SeoConfigurator implements ModuleConfiguratorInterface
{
    /** @var array<string, mixed> */
    private array $overrides = [];

    public function titleSeparator(string $separator): self
    {
        $this->overrides['seo.title_separator'] = $separator;

        return $this;
    }

    public function robotsDefault(string $robots): self
    {
        $this->overrides['seo.robots_default'] = $robots;

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
