# Security Bundle — How-to guides

*How-to — Task-oriented*

Practical recipes for common Security bundle use cases.

---

## Configure Content Security Policy (CSP) headers

Add allowed sources via the `ContentSecurityPolicyInterface`:

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\ContentSecurityPolicyInterface;

$csp = $container->get(ContentSecurityPolicyInterface::class);

$csp->addDirective('script-src', 'https://cdn.example.com');
$csp->addDirective('style-src', ['https://fonts.googleapis.com', "'unsafe-inline'"]);
$csp->addDirective('img-src', ['https:', 'data:']);
$csp->addDirective('connect-src', 'https://api.example.com');
```

To test without blocking resources, enable report-only mode:

```php
<?php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security->cspReportOnly(true);
};
```

This sends `Content-Security-Policy-Report-Only` instead of `Content-Security-Policy`.

---

## Set up IP access control

The `IPAccessControl` supports whitelist and blacklist modes with CIDR notation (IPv4 and IPv6).

### Restrict admin access to specific IPs

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\IPAccessControlInterface;

$ipControl = $container->get(IPAccessControlInterface::class);

$ipControl->addToWhitelist('203.0.113.10');
$ipControl->addToWhitelist('198.51.100.0/24');
```

When a whitelist is defined, only whitelisted IPs can access `/wp-admin` and `/wp-login.php`. All others receive HTTP 403.

### Block specific IPs

```php
$ipControl->addToBlacklist('192.0.2.50');
$ipControl->addToBlacklist('2001:db8::/32');
```

If no whitelist is defined, all IPs are allowed except those on the blacklist.

---

## Customize REST API rate limiting

Modify the global limits:

```php
<?php

use BackTo\Framework\Bundle\Security\RestApiRateLimiter;

$rateLimiter = $container->get(RestApiRateLimiter::class);

$rateLimiter->setDefaultLimit(100);  // requests per window
$rateLimiter->setDefaultWindow(120); // window in seconds
```

Set per-route limits:

```php
$rateLimiter->setRouteLimit('/jwt-auth/', 5, 300);
$rateLimiter->setRouteLimit('/wp/v2/posts', 200, 60);
```

When a client exceeds the limit, the response is HTTP 429 with headers `X-RateLimit-Limit`, `X-RateLimit-Remaining`, and `Retry-After`.

---

## Enable two-factor authentication (2FA)

Enable 2FA globally in your configuration:

```php
<?php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        ->twoFactorEnabled(true)
        ->twoFactorIssuer('MyApp');
};
```

### Set up 2FA for a user

```php
<?php

use BackTo\Framework\Bundle\Security\TwoFactor\TwoFactorSetupManager;

$setupManager = $container->get(TwoFactorSetupManager::class);

$result = $setupManager->setup($userId, $userEmail);
// $result['secret']           — TOTP secret (Base32)
// $result['provisioning_uri'] — otpauth:// URI for QR code
// $result['backup_codes']     — 8 one-time codes (XXXX-XXXX format)
```

### Confirm setup

The user scans the QR code and enters a code to confirm:

```php
$confirmed = $setupManager->confirmSetup($userId, $codeFromUser);
// true = 2FA is now active; false = invalid code, not activated
```

### Regenerate backup codes

```php
$newCodes = $setupManager->regenerateBackupCodes($userId);
```

### Disable 2FA for a user

```php
$setupManager->disableForUser($userId);
```

---

## Configure CORS for headless WordPress

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\CorsManagerInterface;

$cors = $container->get(CorsManagerInterface::class);

$cors->addAllowedOrigin('https://app.example.com');
$cors->addAllowedOrigin('https://staging.example.com');
$cors->addAllowedMethod(['GET', 'POST', 'PUT', 'DELETE']);
$cors->addAllowedHeader(['Content-Type', 'Authorization', 'X-WP-Nonce']);
$cors->setAllowCredentials(true);
$cors->setMaxAge(86400);
```

> **Warning:** The wildcard `*` is not allowed when `allowCredentials` is `true`. List origins explicitly.

---

## Set a password policy

```php
<?php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security->passwordMinLength(16);
};
```

The `PasswordPolicy` rule enforces: minimum length (configurable), at least one uppercase letter, one digit, and one special character. Validation runs on `user_profile_update_errors` and `registration_errors`.

---

## Configure auto-update policy

```php
<?php

use BackTo\Framework\Bundle\Security\AutoUpdatePolicy;

$autoUpdate = $container->get(AutoUpdatePolicy::class);

$autoUpdate->setMajorCore(false);
$autoUpdate->setMinorCore(true);
$autoUpdate->setPlugins(false);
$autoUpdate->setThemes(false);
$autoUpdate->setTranslations(true);

$autoUpdate->setAllowedPlugins(['akismet/akismet.php']);
$autoUpdate->setAllowedThemes(['twentytwentyfour']);
```

---

## Restrict REST API access

REST API authentication is required by default (`security.rest_api_require_auth = true`). Public routes (`/oembed/*`, `/wp-site-health/*`) are exempt.

To add custom public routes, pass additional patterns when the service is registered:

```php
<?php

use BackTo\Framework\Bundle\Security\RestApiSecurity;

$restSecurity = new RestApiSecurity(
    $hookDispatcher,
    $requestContext,
    $userContext,
    $siteContext,
    additionalPublicPatterns: [
        '#^/myapp/v1/public/#',
        '#^/wc/store/#',
    ]
);
```

Unauthenticated users receive HTTP 401 on protected routes.

---

## Obfuscate the login URL

```php
<?php

use BackTo\Framework\Bundle\Security\AdminUrlObfuscation;

$obfuscation = $container->get(AdminUrlObfuscation::class);
$obfuscation->setLoginSlug('my-secure-login');
```

Direct access to `/wp-login.php` returns a 404. The `login_url`, `logout_url`, and `site_url` filters are updated automatically.

---

## Configure security notifications

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\SecurityNotifierInterface;

$notifier = $container->get(SecurityNotifierInterface::class);

$notifier->setRecipients(['admin@example.com', 'security@example.com']);
$notifier->setFailedLoginThreshold(5);
$notifier->addCriticalEvent('custom_event');
```

---

## Run a security scan

Send a GET request to the scan endpoint (requires `manage_options` capability):

```
GET /wp-json/backto/v1/security/scan
```

The response includes file integrity results (SHA-256 baseline comparison) and malware scan results (PHP files in the uploads directory).

---

## Purge old audit log events

```php
<?php

use BackTo\Framework\Bundle\Security\SecurityAuditLogger;

$auditLogger = $container->get(SecurityAuditLogger::class);
$purged = $auditLogger->purgeOldEvents(90); // events older than 90 days
```

Export is available from the Audit Log admin page. A 60-second cooldown prevents repeated exports.

---

## Test code that uses security services

Mock the port interfaces in unit tests:

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\LoginThrottleInterface;

$throttle = $this->createMock(LoginThrottleInterface::class);
$throttle->method('isThrottled')->willReturn(false);

$loginHardening = new LoginHardening($hookDispatcher, $throttle, $ipResolver, $logger);
```

All security rules depend on port interfaces, never on WordPress directly. Replace any adapter with a mock or test double.
