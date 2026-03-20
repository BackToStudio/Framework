<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Preset;

use BackTo\Framework\Bundle\Gdpr\Contracts\TrackingScriptInterface;

final class GoogleTagManagerScript implements TrackingScriptInterface
{
    public function __construct(
        private readonly string $containerId,
    ) {
    }

    public function getHandle(): string
    {
        return 'google-tag-manager';
    }

    public function getCategoryKey(): string
    {
        return 'analytics';
    }

    public function getSource(): string
    {
        return "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':"
            . "new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],"
            . "j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src="
            . "'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);"
            . "})(window,document,'script','dataLayer','" . $this->containerId . "');";
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
        return 1;
    }
}
