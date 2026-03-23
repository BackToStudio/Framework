<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Preset;

use BackTo\Framework\Bundle\Gdpr\Contracts\TrackingScriptInterface;

final class GoogleAnalyticsScript implements TrackingScriptInterface
{
    /**
     * @param string $measurementId Google Analytics measurement ID (e.g. "G-XXXXXXXXXX").
     */
    public function __construct(
        private readonly string $measurementId,
    ) {
        if (\preg_match('/^[A-Z0-9]+-[A-Z0-9]+$/i', $measurementId) !== 1) {
            throw new \InvalidArgumentException(\sprintf('Invalid Google Analytics measurement ID: "%s".', $measurementId));
        }
    }

    public function getHandle(): string
    {
        return 'google-analytics';
    }

    public function getCategoryKey(): string
    {
        return 'analytics';
    }

    public function getSource(): string
    {
        return "window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}"
            . "gtag('js',new Date());gtag('config','" . $this->measurementId . "');";
    }

    public function isInline(): bool
    {
        return true;
    }

    public function getLocation(): string
    {
        return 'head';
    }

    public function getPriority(): int
    {
        return 5;
    }
}
