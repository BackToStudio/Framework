<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Gdpr\Contracts\TrackingScriptRegistryInterface;

class TrackingScriptRegistry implements RegistryInterface, TrackingScriptRegistryInterface
{
    /** @var TrackingScriptInterface[] */
    private array $scripts = [];

    public function add(TrackingScriptInterface $script): TrackingScriptRegistryInterface
    {
        $this->scripts[] = $script;

        return $this;
    }

    /**
     * @return TrackingScriptInterface[]
     */
    public function getScripts(): array
    {
        return $this->scripts;
    }

    /**
     * @return TrackingScriptInterface[]
     */
    public function getScriptsByCategory(string $categoryKey): array
    {
        return array_values(
            array_filter(
                $this->scripts,
                static fn (TrackingScriptInterface $script): bool => $script->getCategoryKey() === $categoryKey
            )
        );
    }
}
