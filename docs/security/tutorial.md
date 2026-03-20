# Getting started with the Security bundle

*Tutorial — Learning-oriented*

This tutorial walks you through enabling the Security bundle in a plugin, customizing a few settings, and verifying that the protections are active. By the end you will have a hardened WordPress site with security headers, login throttling, and audit logging.

## Prerequisites

- A WordPress plugin or theme using the BackTo Framework
- The framework's DI container configured
- Admin access to the WordPress site

## Step 1: Register the Security extension

Add `SecurityExtension` to your plugin's extensions:

```php
<?php

namespace MyPlugin;

use BackTo\Framework\Bundle\Security\SecurityExtension;
use BackTo\Framework\Plugin\AbstractPlugin;

class MyPlugin extends AbstractPlugin
{
    protected function getExtensions(): array
    {
        return [
            new SecurityExtension(),
        ];
    }
}
```

All security rules are now auto-discovered and active with their defaults. No further configuration is required to get baseline protection.

## Step 2: Verify the security headers

Open your site in a browser and inspect the response headers (DevTools > Network tab > select the page request > Headers). You should see:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- No `X-Powered-By` header

These are sent automatically by `HttpHeadersHardening`.

## Step 3: Customize the configuration

Create `config/security.php` in your plugin to override defaults:

```php
<?php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        ->passwordMinLength(16)
        ->maxConcurrentSessions(1)
        ->restApiRequireAuth(true);
};
```

This enforces a 16-character minimum password, limits each user to one active session, and requires authentication for REST API access.

## Step 4: Trigger a login event

Log out and log back in to your WordPress site. The `SecurityAuditLogger` records this event automatically.

## Step 5: Check the audit log

In the WordPress admin, navigate to the **Audit Log** menu item (shield icon). You should see your login event recorded with:

- **Event:** `login_success`
- **Severity:** Info
- **Context:** your username, IP address, and timestamp

## Step 6: Test login throttling

Open an incognito window and attempt to log in with an incorrect password several times. After repeated failures, `LoginHardening` will temporarily block further attempts from your IP. You will see a generic error message — the bundle never reveals whether the username or password was wrong.

## Next steps

- See [Common tasks](how-to/README.md) for recipes like CSP configuration, IP access control, and two-factor authentication
- See [API reference](reference/README.md) for the complete list of security rules and their hooks
- See [Architecture](explanation/README.md) to understand why the bundle is designed this way
