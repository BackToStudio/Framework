# Protect comment forms with honeypot and content filtering

`CommentSpamProtection` blocks spam without CAPTCHAs using three techniques:

1. **Honeypot field** — A hidden form field bots fill in but humans cannot see.
2. **Referer validation** — Rejects submissions not originating from your site.
3. **Content analysis** — Blocks comments with excessive links or dangerous HTML patterns.

Enable it:

```php
<?php

$services->set(\BackTo\Framework\Bundle\Security\CommentSpamProtection::class)
    ->autowire()
    ->autoconfigure();
```

### Customize thresholds

```php
<?php

use BackTo\Framework\Bundle\Security\CommentSpamProtection;

$spam = $container->get(CommentSpamProtection::class);

// Allow at most 1 link per comment (default: 2)
$spam->setMaxLinksAllowed(1);

// Change the honeypot field name (default: 'website_url_confirm')
$spam->setHoneypotFieldName('confirm_url');
```

The honeypot renders automatically on `comment_form` — a hidden `<div>` with `position: absolute; left: -9999px` and `aria-hidden="true"`. It validates on `preprocess_comment`.

### Detected spam patterns

- BBCode URLs (`[url=...]`)
- HTML anchor tags, script tags, iframes, objects, embeds, forms
- HTML event handlers (`onclick`, `onload`, etc.)

Blocked comments receive HTTP 403.
