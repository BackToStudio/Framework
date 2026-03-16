<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Preset;

use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;

final class GoogleAnalyticsScript implements TrackingScriptInterface
{
    public function __construct(
        private readonly string $measurementId,
    ) {
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
