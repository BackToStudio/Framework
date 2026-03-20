# SEO Bundle

Structured data (JSON-LD), meta tag access, and social links for WordPress themes, with built-in support for Yoast SEO and SEOPress.

## Overview

The SEO bundle generates schema.org structured data using a fluent PHP API, automatically injects it into `<head>`, and provides a unified interface to read meta and social link data from whichever SEO plugin is active. Plugin-native schema output is disabled by default to avoid duplicates.

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;

$schema = Schema::article()
    ->headline('My Post Title')
    ->author(Schema::person()->name('Jane Doe'))
    ->datePublished('2025-01-15');
```

## Documentation

| Document | Type | Description |
|---|---|---|
| [Getting started](tutorial.md) | Tutorial | Add Article schema to a blog post and validate it |
| [Common tasks](how-to/README.md) | How-to | Practical recipes for schemas, providers, and configuration |
| [API reference](reference.md) | Reference | Complete class, method, and hook documentation |
| [Architecture](explanation.md) | Explanation | Design decisions: @graph, provider abstraction, pipeline |
