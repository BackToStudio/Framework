# GDPR Bundle

A GDPR-compliant cookie consent system with a banner, consent categories, tracking script management, and preset integrations for popular analytics and marketing tools.

## Overview

The GDPR bundle manages the full consent lifecycle: it displays a consent banner, stores user choices in a cookie, and conditionally loads tracking scripts based on the accepted categories. It ships with presets for Google Analytics, Google Tag Manager, Google Ads, HubSpot, and Hotjar.

```php
<?php

use BackTo\Framework\Bundle\Gdpr\GdprExtension;

$kernel->addExtension(new GdprExtension());
```

## Documentation

| Document | Type | Description |
|---|---|---|
| [Getting started](tutorial.md) | Tutorial | Set up the consent banner and add your first tracking script |
| [Common tasks](how-to.md) | How-to | Add presets, custom scripts, server-side consent checks |
| [API reference](reference.md) | Reference | Interfaces, entities, presets, registries |
| [Architecture](explanation.md) | Explanation | Consent flow, rendering separation, cookie security |
