<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Preset;

use BackTo\Framework\Bundle\Gdpr\Contracts\TrackingScriptInterface;

final class GtagLoaderScript implements TrackingScriptInterface
{
    public function __construct(
        private readonly string $trackingId,
        private string $categoryKey = 'analytics',
    ) {
    }

    public function getHandle(): string
    {
        return 'gtag-loader';
    }

    public function getCategoryKey(): string
    {
        return $this->categoryKey;
    }

    public function getSource(): string
    {
        return 'https://www.googletagmanager.com/gtag/js?id=' . $this->trackingId;
    }

    public function isInline(): bool
    {
        return false;
    }

    public function getLocation(): string
    {
        return 'head';
    }

    public function getPriority(): int
    {
        return 4;
    }
}
