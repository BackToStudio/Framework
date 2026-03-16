<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Preset;

use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;

final class GoogleAdsScript implements TrackingScriptInterface
{
    public function __construct(
        private readonly string $conversionId,
    ) {
    }

    public function getHandle(): string
    {
        return 'google-ads';
    }

    public function getCategoryKey(): string
    {
        return 'marketing';
    }

    public function getSource(): string
    {
        return "window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}"
            . "gtag('js',new Date());gtag('config','" . $this->conversionId . "');";
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
