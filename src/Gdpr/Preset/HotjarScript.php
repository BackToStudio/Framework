<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Preset;

use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;

final class HotjarScript implements TrackingScriptInterface
{
    public function __construct(
        private readonly string $siteId,
    ) {
    }

    public function getHandle(): string
    {
        return 'hotjar';
    }

    public function getCategoryKey(): string
    {
        return 'analytics';
    }

    public function getSource(): string
    {
        return "(function(h,o,t,j,a,r){h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};"
            . "h._hjSettings={hjid:" . $this->siteId . ",hjsv:6};"
            . "a=o.getElementsByTagName('head')[0];"
            . "r=o.createElement('script');r.async=1;"
            . "r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;"
            . "a.appendChild(r);"
            . "})(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');";
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
        return 10;
    }
}
