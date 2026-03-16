<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Preset;

use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;

final class HubSpotScript implements TrackingScriptInterface
{
    public function __construct(
        private readonly string $portalId,
    ) {
    }

    public function getHandle(): string
    {
        return 'hubspot';
    }

    public function getCategoryKey(): string
    {
        return 'marketing';
    }

    public function getSource(): string
    {
        return 'https://js.hs-scripts.com/' . $this->portalId . '.js';
    }

    public function isInline(): bool
    {
        return false;
    }

    public function getLocation(): string
    {
        return 'footer';
    }

    public function getPriority(): int
    {
        return 10;
    }
}
