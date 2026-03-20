# GDPR Bundle — How-to guides

*How-to — Task-oriented*

Practical recipes for common GDPR consent tasks.

---

## Add Google Tag Manager

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\GoogleTagManagerScript;

$services->set(GoogleTagManagerScript::class)
    ->args(['GTM-XXXXXXX']);
```

Inline script in the `<head>`, priority 1 (loaded first). Category: `analytics`.

---

## Add Google Ads

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\GoogleAdsScript;

$services->set(GoogleAdsScript::class)
    ->args(['AW-XXXXXXXXX']);
```

Inline script in the `<head>`, priority 5. Category: `marketing`.

---

## Add HubSpot

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\HubSpotScript;

$services->set(HubSpotScript::class)
    ->args(['12345678']);
```

External script in the `<footer>`, priority 10. Category: `marketing`.

---

## Add Hotjar

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\HotjarScript;

$services->set(HotjarScript::class)
    ->args(['1234567']);
```

Inline script in the `<head>`, priority 10. Category: `analytics`.

---

## Create a custom tracking script

Use the `TrackingScript` entity directly:

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Entity\TrackingScript;

$services->set('custom_pixel', TrackingScript::class)
    ->args([
        'facebook-pixel',       // handle
        'marketing',            // categoryKey
        '!function(f,b,e,...)', // source (inline code)
        true,                   // inline
        'head',                 // location ('head' or 'footer')
        5,                      // priority
    ]);
```

Or implement `TrackingScriptInterface` for full control.

---

## Check consent server-side

Inject `ConsentStorageInterface` into your service:

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentStorageInterface;

class MyService
{
    public function __construct(
        private readonly ConsentStorageInterface $consentStorage,
    ) {}

    public function process(): void
    {
        if ($this->consentStorage->hasConsent('analytics')) {
            // User has accepted analytics cookies.
        }
    }
}
```
