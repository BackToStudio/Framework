<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Bundle\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Bundle\Gdpr\Contracts\TrackingScriptRegistryInterface;

final class TrackingScriptRegistry implements RegistryInterface, TrackingScriptRegistryInterface
{
    /** @var TrackingScriptInterface[] */
    private array $scripts = [];

    public function add(TrackingScriptInterface $script): TrackingScriptRegistryInterface
    {
        $this->scripts[] = $script;

        return $this;
    }

    
    public function getScripts(): array
    {
        return $this->scripts;
    }

    
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
