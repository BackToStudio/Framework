# Implement a tracking script with full control

For complex integrations, implement `TrackingScriptInterface`:

```php
<?php

namespace MyPlugin\Gdpr;

use BackTo\Framework\Bundle\Gdpr\Contracts\TrackingScriptInterface;

final class SegmentScript implements TrackingScriptInterface
{
    public function __construct(
        private readonly string $writeKey,
    ) {}

    public function getHandle(): string { return 'segment'; }
    public function getCategoryKey(): string { return 'analytics'; }
    public function isInline(): bool { return true; }
    public function getLocation(): string { return 'head'; }
    public function getPriority(): int { return 3; }

    public function getSource(): string
    {
        return <<<JS
        !function(){var i="analytics",analytics=window[i]=window[i]||[];
        analytics.load("{$this->writeKey}");analytics.page()}();
        JS;
    }
}
```

Register it:

```php
$services->set(SegmentScript::class)
    ->args(['YOUR_WRITE_KEY'])
    ->tag('wordpress.tracking_script');
```
