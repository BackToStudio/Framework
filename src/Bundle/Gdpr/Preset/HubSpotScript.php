<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Preset;

use BackTo\Framework\Bundle\Gdpr\Contracts\TrackingScriptInterface;

final class HubSpotScript implements TrackingScriptInterface
{
    /**
     * @param string $portalId HubSpot numeric portal ID.
     */
    public function __construct(
        private readonly string $portalId,
    ) {
        if (\preg_match('/^\d+$/', $portalId) !== 1) {
            throw new \InvalidArgumentException(\sprintf('Invalid HubSpot portal ID: "%s". Must be numeric.', $portalId));
        }
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
