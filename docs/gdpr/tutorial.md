# Getting started with the GDPR Bundle

*Tutorial — Learning-oriented*

This tutorial walks you through setting up a GDPR-compliant consent banner with tracking scripts. By the end, your site will show a cookie banner, respect user consent, and conditionally load analytics.

## Prerequisites

- A WordPress plugin or theme using the BackTo Framework
- The framework's DI container configured

## Step 1: Register the extension

Add `GdprExtension` to your kernel before booting:

```php
<?php

use BackTo\Framework\Bundle\Gdpr\GdprExtension;

$kernel->addExtension(new GdprExtension());
$kernel->boot();
```

## Step 2: Define consent categories

Create categories using the `ConsentCategory` entity. Each category appears in the banner:

```php
<?php

declare(strict_types=1);

namespace App\Gdpr;

use BackTo\Framework\Bundle\Gdpr\Entity\ConsentCategory;

final class ConsentCategories
{
    public static function necessary(): ConsentCategory
    {
        return new ConsentCategory(
            key: 'necessary',
            label: 'Necessary cookies',
            description: 'Required for the site to function.',
            required: true,
        );
    }

    public static function analytics(): ConsentCategory
    {
        return new ConsentCategory(
            key: 'analytics',
            label: 'Analytics cookies',
            description: 'Help us understand how you use the site.',
        );
    }

    public static function marketing(): ConsentCategory
    {
        return new ConsentCategory(
            key: 'marketing',
            label: 'Marketing cookies',
            description: 'Used for advertising tracking.',
        );
    }
}
```

Register them as services tagged `wordpress.consent_category` (auto-configured if they implement `ConsentCategoryInterface`).

## Step 3: Add a tracking script

Use the built-in Google Analytics preset:

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\GtagLoaderScript;
use BackTo\Framework\Bundle\Gdpr\Preset\GoogleAnalyticsScript;

// In your service configuration:
$services->set(GtagLoaderScript::class)
    ->args(['G-XXXXXXXXXX']);

$services->set(GoogleAnalyticsScript::class)
    ->args(['G-XXXXXXXXXX']);
```

## Step 4: Verify

Visit your site. The consent banner appears on the first visit (`wp_footer` hook). Accept the analytics category — the page reloads and the Google Analytics scripts are injected. Decline — they are not loaded.

## Next steps

- See [Common tasks](how-to/README.md) for adding other presets and custom scripts
- See [Architecture](explanation/README.md) to understand the consent flow
