# How to Manage GDPR Consent and Tracking Scripts

The GDPR module provides a consent banner, cookie-based consent storage, and conditional script loading. Scripts only execute when the user has granted consent for their category.

## Define consent categories

Create a class implementing `ConsentCategoryInterface` for each category you need:

```php
<?php

namespace MyTheme\Gdpr;

use BackTo\Framework\Gdpr\Contracts\ConsentCategoryInterface;

class AnalyticsCategory implements ConsentCategoryInterface
{
    public function getKey(): string { return 'analytics'; }
    public function getLabel(): string { return 'Analytics'; }
    public function getDescription(): string { return 'Cookies used for traffic analysis and statistics.'; }
    public function isRequired(): bool { return false; }
}
```

Or use the `ConsentCategory` entity directly in your service configuration:

```php
use BackTo\Framework\Gdpr\Entity\ConsentCategory;

$services->set('app.category.analytics', ConsentCategory::class)
    ->args(['analytics', 'Analytics', 'Cookies used for traffic analysis.', false])
    ->tag('wordpress.consent_category');
```

Categories tagged with `wordpress.consent_category` are automatically collected into the `ConsentCategoryRegistry`.

## Add a tracking script

Implement `TrackingScriptInterface` or use the `TrackingScript` entity:

```php
use BackTo\Framework\Gdpr\Entity\TrackingScript;

$services->set('app.script.ga', TrackingScript::class)
    ->args([
        'google-analytics',  // handle
        'analytics',         // categoryKey (must match a consent category)
        "gtag('config', 'G-XXXXXXX');", // source (inline JS or URL)
        true,                // inline (true = inline code, false = external URL)
        'head',              // location ('head' or 'footer')
        5,                   // priority (lower = earlier execution)
    ])
    ->tag('wordpress.tracking_script');
```

## Use built-in presets

The framework ships with preconfigured scripts for popular services. Each preset only requires the service's tracking ID.

### Google Tag Manager

```php
use BackTo\Framework\Gdpr\Preset\GoogleTagManagerScript;

$services->set(GoogleTagManagerScript::class)
    ->args(['GTM-XXXXXXX'])
    ->tag('wordpress.tracking_script');
```

### Google Analytics (GA4)

GA4 requires the `gtag.js` loader and the configuration script. Register both:

```php
use BackTo\Framework\Gdpr\Preset\GtagLoaderScript;
use BackTo\Framework\Gdpr\Preset\GoogleAnalyticsScript;

$services->set(GtagLoaderScript::class)
    ->args(['G-XXXXXXX'])
    ->tag('wordpress.tracking_script');

$services->set(GoogleAnalyticsScript::class)
    ->args(['G-XXXXXXX'])
    ->tag('wordpress.tracking_script');
```

### Google Ads

```php
use BackTo\Framework\Gdpr\Preset\GtagLoaderScript;
use BackTo\Framework\Gdpr\Preset\GoogleAdsScript;

$services->set('app.gtag_loader_ads', GtagLoaderScript::class)
    ->args(['AW-XXXXXXX', 'marketing'])
    ->tag('wordpress.tracking_script');

$services->set(GoogleAdsScript::class)
    ->args(['AW-XXXXXXX'])
    ->tag('wordpress.tracking_script');
```

### Hotjar

```php
use BackTo\Framework\Gdpr\Preset\HotjarScript;

$services->set(HotjarScript::class)
    ->args(['1234567'])
    ->tag('wordpress.tracking_script');
```

### HubSpot

```php
use BackTo\Framework\Gdpr\Preset\HubSpotScript;

$services->set(HubSpotScript::class)
    ->args(['12345678'])
    ->tag('wordpress.tracking_script');
```

## Preset summary

| Preset | Category | Location | Param |
|--------|----------|----------|-------|
| `GoogleTagManagerScript` | analytics | head | Container ID (`GTM-XXX`) |
| `GtagLoaderScript` | analytics* | head | Tracking ID (loads `gtag.js`) |
| `GoogleAnalyticsScript` | analytics | head | Measurement ID (`G-XXX`) |
| `GoogleAdsScript` | marketing | head | Conversion ID (`AW-XXX`) |
| `HotjarScript` | analytics | head | Site ID |
| `HubSpotScript` | marketing | footer | Portal ID |

*`GtagLoaderScript` accepts an optional second argument to override the category (e.g., `'marketing'` for Google Ads).*

## Check consent in your own code

Inject `ConsentStorageInterface` to read consent state server-side:

```php
use BackTo\Framework\Gdpr\Contracts\ConsentStorageInterface;

class MyService
{
    public function __construct(private ConsentStorageInterface $consentStorage)
    {
    }

    public function shouldTrack(): bool
    {
        return $this->consentStorage->hasConsent('analytics');
    }
}
```

## Create a custom tracking script preset

Implement `TrackingScriptInterface` with hardcoded defaults:

```php
<?php

namespace MyTheme\Gdpr;

use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;

class FacebookPixelScript implements TrackingScriptInterface
{
    public function __construct(private readonly string $pixelId)
    {
    }

    public function getHandle(): string { return 'facebook-pixel'; }
    public function getCategoryKey(): string { return 'marketing'; }
    public function getSource(): string
    {
        return "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?"
            . "n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;"
            . "n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;"
            . "t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,"
            . "document,'script','https://connect.facebook.net/en_US/fbevents.js');"
            . "fbq('init','" . $this->pixelId . "');fbq('track','PageView');";
    }
    public function isInline(): bool { return true; }
    public function getLocation(): string { return 'head'; }
    public function getPriority(): int { return 10; }
}
```

Register it in your DI config:

```php
$services->set(FacebookPixelScript::class)
    ->args(['1234567890'])
    ->tag('wordpress.tracking_script');
```
