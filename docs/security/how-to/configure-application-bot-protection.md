# Configure application bot protection

*How-to — Task-oriented*

This guide covers the application layer of bot protection: the BackTo Framework Security Bundle. This layer catches bots that reach PHP despite CDN, web server, and system-level protections.

## Prerequisites

- A WordPress site using the BackTo Framework
- The Security Bundle enabled (see [Getting started](../tutorial.md))

## Configure bot protection in config/security.php

All bot configuration is defined in `config/security.php`. These parameters feed both the application layer (rate limiter, IP control) and the server configuration generator.

```php
<?php
// config/security.php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        // Bots blocked at both server and application level
        ->botBlockedUserAgents([
            'SemrushBot', 'AhrefsBot', 'DotBot', 'MJ12bot',
            'BLEXBot', 'PetalBot', 'DataForSeoBot', 'GPTBot', 'CCBot',
        ])

        // IP/CIDR ranges to block
        ->botBlockedIps(['192.0.2.0/24'])

        // Sensitive endpoints (stricter rate limiting)
        ->botSensitiveEndpoints(['wp-login.php', 'xmlrpc.php', 'wp-cron.php'])

        // Global and sensitive rate limits
        ->botGlobalRateLimit(10, 20)
        ->botSensitiveRateLimit(2, 3)

        // Max simultaneous connections per IP
        ->botMaxConnectionsPerIp(20)

        // Block requests with no User-Agent header
        ->botBlockEmptyUserAgent(true)

        // Require authentication for REST API
        ->restApiRequireAuth(true);
};
```

## REST API rate limiting

The `RestApiRateLimiter` limits requests per IP on REST endpoints. It returns HTTP 429 with `X-RateLimit-Limit`, `X-RateLimit-Remaining`, and `Retry-After` headers.

```php
<?php

use BackTo\Framework\Bundle\Security\Network\RestApiRateLimiter;

$rateLimiter = $container->get(RestApiRateLimiter::class);

// Global limit
$rateLimiter->setDefaultLimit(30);
$rateLimiter->setDefaultWindow(60);

// Per-route limits
$rateLimiter->setRouteLimit('/wp/v2/users', 5, 60);
$rateLimiter->setRouteLimit('/jwt-auth/', 3, 300);
$rateLimiter->setRouteLimit('/wp/v2/posts', 60, 60);
```

## IP access control

`IPAccessControl` manages whitelist and blacklist at the application level. Supports CIDR notation (IPv4 and IPv6).

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\IPAccessControlInterface;

$ipControl = $container->get(IPAccessControlInterface::class);

// Block IPs
$ipControl->addToBlacklist('192.0.2.0/24');
$ipControl->addToBlacklist('198.51.100.50');

// Restrict admin to specific IPs
$ipControl->addToWhitelist('203.0.113.10');
$ipControl->addToWhitelist('198.51.100.0/24');
```

When a whitelist is defined, only those IPs can access `/wp-admin` and `/wp-login.php`. All others receive HTTP 403.

## Comment form protection

`CommentSpamProtection` blocks automated submissions using three techniques:

1. **Honeypot**: a hidden form field that bots fill in but humans cannot see.
2. **Referer validation**: rejects submissions not originating from your site.
3. **Content analysis**: blocks comments with excessive links or dangerous HTML patterns.

```php
<?php

use BackTo\Framework\Bundle\Security\Hardening\CommentSpamProtection;

$spam = $container->get(CommentSpamProtection::class);

// Allow at most 1 link per comment (default: 2)
$spam->setMaxLinksAllowed(1);
```

See [Protect comment forms with honeypot and content filtering](./protect-comment-forms-with-honeypot-and-content-filtering.md).

## Generate server configuration

Once `config/security.php` is configured, generate the corresponding Nginx or Apache rules:

```bash
# Nginx
wp backto:generate-server-config --server=nginx --output=file --dir=/etc/nginx/conf.d

# Apache
wp backto:generate-server-config --server=apache --output=file --dir=/var/www/html

# Both
wp backto:generate-server-config --server=both --output=file
```

See [Generate server bot protection config](./generate-server-bot-protection-config.md).

## Monitoring

Use the `SecurityAuditLogger` to track blocked attempts and adjust rules:

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\AuditLogRepositoryInterface;

$auditLog = $container->get(AuditLogRepositoryInterface::class);

// Query recent events
$events = $auditLog->findRecent(50);
```

Rate limiting and IP blocking events are logged automatically with the source IP, target endpoint, and timestamp.

## See also

- [Protect against bots with Nginx](./protect-against-bots-nginx.md)
- [Protect against bots with Apache](./protect-against-bots-apache.md)
- [Customize REST API rate limiting](./customize-rest-api-rate-limiting.md)
- [Set up IP access control](./set-up-ip-access-control.md)
- [Restrict REST API access](./restrict-rest-api-access.md)
