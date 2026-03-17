# How to Use the Security Module

The Security module provides a comprehensive set of security rules that harden your WordPress installation. Each rule implements `SecurityRuleInterface` and is auto-registered via the `wordpress.security_rule` tag.

## How it works

Security rules are autoconfigured services. When the container is compiled, `RegisterSecurityRulePass` collects every `SecurityRuleInterface` into the `SecurityRuleRegistry`. Each rule hooks into WordPress automatically.

No manual registration is required — add the Security bundle to your kernel and all rules activate.

## Configure security parameters

Create a `config/security.php` file and use the fluent `SecurityConfigurator`:

```php
<?php

use BackTo\Framework\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        ->headersEnabled(true)
        ->xmlrpcDisabled(true)
        ->hideVersion(true)
        ->cspReportOnly(false)
        ->passwordMinLength(16)
        ->maxConcurrentSessions(3)
        ->restApiRequireAuth(true)
        ->disableFileEditor(true)
        ->twoFactorEnabled(true)
        ->twoFactorIssuer('MonApp');
};
```

Only call the methods you want to override — unset values keep their defaults.

### Available methods

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `headersEnabled(bool)` | `bool` | `true` | Send hardened security headers |
| `xmlrpcDisabled(bool)` | `bool` | `true` | Disable XML-RPC entirely |
| `hideVersion(bool)` | `bool` | `true` | Remove WP version from output |
| `cspReportOnly(bool)` | `bool` | `false` | CSP in report-only mode |
| `passwordMinLength(int)` | `int` | `12` | Minimum password length |
| `maxConcurrentSessions(int)` | `int` | `1` | Max concurrent sessions per user |
| `restApiRequireAuth(bool)` | `bool` | `true` | Require auth for REST API |
| `disableFileEditor(bool)` | `bool` | `true` | Disable theme/plugin file editor |
| `twoFactorEnabled(bool)` | `bool` | `false` | Enable TOTP 2FA |
| `twoFactorIssuer(string)` | `string` | `'WordPress'` | Issuer shown in authenticator apps |

## Use input sanitization

Inject `InputSanitizerInterface` to sanitize user input:

```php
use BackTo\Framework\Security\Contracts\InputSanitizerInterface;

class ContactFormHandler
{
    public function __construct(private InputSanitizerInterface $sanitizer) {}

    public function handle(array $data): void
    {
        $name  = $this->sanitizer->sanitizeText($data['name']);
        $email = $this->sanitizer->sanitizeEmail($data['email']);
        $url   = $this->sanitizer->sanitizeUrl($data['website']);
        $body  = $this->sanitizer->sanitizeHtml($data['message'], [
            'p'  => [],
            'a'  => ['href' => true],
            'em' => [],
        ]);
    }
}
```

## Use output escaping

Inject `OutputEscaperInterface` to escape output:

```php
use BackTo\Framework\Security\Contracts\OutputEscaperInterface;

class TemplateRenderer
{
    public function __construct(private OutputEscaperInterface $escaper) {}

    public function render(string $title, string $url): string
    {
        return sprintf(
            '<a href="%s">%s</a>',
            $this->escaper->url($url),
            $this->escaper->html($title)
        );
    }
}
```

Methods: `html()`, `attr()`, `url()`, `js()`, `textarea()`.

## Use nonce management

Inject `NonceManagerInterface` for CSRF protection:

```php
use BackTo\Framework\Security\Contracts\NonceManagerInterface;

class FormController
{
    public function __construct(private NonceManagerInterface $nonce) {}

    public function renderForm(): string
    {
        $token = $this->nonce->create('my_form_action');
        return '<input type="hidden" name="' . $this->nonce->getFieldName() . '" value="' . $token . '">';
    }

    public function handleSubmit(array $post): bool
    {
        return $this->nonce->verify($post[$this->nonce->getFieldName()], 'my_form_action');
    }
}
```

## Set up Content Security Policy

The `ContentSecurityPolicyManager` ships with secure defaults. Customize directives in your service:

```php
use BackTo\Framework\Security\Contracts\ContentSecurityPolicyInterface;

class MyThemeSetup
{
    public function __construct(private ContentSecurityPolicyInterface $csp) {}

    public function configure(): void
    {
        $this->csp->addDirective('script-src', 'https://cdn.example.com');
        $this->csp->addDirective('img-src', 'https://images.example.com');
    }
}
```

Use the nonce for inline scripts:

```php
$nonce = $this->csp->getNonce();
echo '<script nonce="' . $nonce . '">/* safe inline JS */</script>';
```

## Configure CORS

Inject `CorsManagerInterface`:

```php
use BackTo\Framework\Security\Contracts\CorsManagerInterface;

$cors->addAllowedOrigin('https://app.example.com');
$cors->addAllowedMethod(['GET', 'POST']);
$cors->addAllowedHeader('Authorization');
$cors->setAllowCredentials(true);
$cors->setMaxAge(3600);
```

## Set up Two-Factor Authentication

The `TwoFactorAuthentication` rule intercepts login at priority 40 (after `LoginHardening` at 30).

### Enable 2FA for a user

```php
use BackTo\Framework\Security\TwoFactor\TwoFactorAuthentication;

$result = $twoFactor->setup($userId, $user->user_email);
// $result = [
//     'secret' => 'BASE32SECRET...',
//     'provisioning_uri' => 'otpauth://totp/WordPress:user@example.com?...',
//     'backup_codes' => ['code1', 'code2', ...],
// ]

// Show QR code from provisioning_uri, then confirm:
$confirmed = $twoFactor->confirmSetup($userId, $codeFromApp);
```

### Disable 2FA

```php
$twoFactor->disableForUser($userId);
```

### Regenerate backup codes

```php
$newCodes = $twoFactor->regenerateBackupCodes($userId);
// Show these to the user once — they are hashed before storage
```

## Use IP access control

Inject `IPAccessControlInterface`:

```php
use BackTo\Framework\Security\Contracts\IPAccessControlInterface;

$ipControl->addToWhitelist('203.0.113.50');
$ipControl->addToBlacklist('198.51.100.0/24');

if ($ipControl->isBlocked($clientIp)) {
    // deny access
}
```

## Query the audit log

The `SecurityAuditLogger` tracks login events, role changes, critical option updates, plugin activations, and more.

```php
use BackTo\Framework\Security\SecurityAuditLogger;

$events = $auditLogger->getAuditLog(limit: 50, offset: 0);
$auditLogger->purgeOldEvents(olderThanDays: 90);
```

Events are also available via the REST API endpoint `GET /backto/v1/security/audit-log` (requires `manage_options`).

## Use the REST API endpoints

Three REST routes are available (all require `manage_options` capability):

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/backto/v1/security/audit-log` | GET | Query audit log events with filters (`event`, `severity`, `per_page`, `page`) |
| `/backto/v1/security/health` | GET | Security health check status |
| `/backto/v1/security/scan` | GET | Trigger file integrity + malware scan |

## Configure security notifications

The `SecurityNotifier` sends emails on critical events (login anomalies, malware detection, integrity failures, etc.):

```php
use BackTo\Framework\Security\Contracts\SecurityNotifierInterface;

$notifier->setRecipients(['security@example.com', 'admin@example.com']);
$notifier->notify('custom_event', 'warning', ['detail' => 'value']);
```

## Create a custom security rule

Implement `SecurityRuleInterface` to add your own:

```php
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

class DisableRssFeeds implements Hooks, SecurityRuleInterface
{
    public function __construct(private HookDispatcherInterface $hookDispatcher) {}

    public function getName(): string
    {
        return 'disable_rss_feeds';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('do_feed', [$this, 'disableFeed']);
        $this->hookDispatcher->addAction('do_feed_rdf', [$this, 'disableFeed']);
        $this->hookDispatcher->addAction('do_feed_rss', [$this, 'disableFeed']);
        $this->hookDispatcher->addAction('do_feed_rss2', [$this, 'disableFeed']);
        $this->hookDispatcher->addAction('do_feed_atom', [$this, 'disableFeed']);
    }

    public function disableFeed(): void
    {
        wp_die('RSS feeds are disabled.', '', ['response' => 403]);
    }
}
```

The rule is autoconfigured — no manual registration needed.

## Included security rules

| Rule | What it does |
|------|-------------|
| `LoginHardening` | Throttles brute-force login attempts (IP + account), generic error messages |
| `TwoFactorAuthentication` | TOTP-based 2FA with backup codes |
| `SecurityAuditLogger` | Persistent audit log for security events |
| `HttpHeadersHardening` | Security headers (X-Frame-Options, X-Content-Type-Options, etc.) |
| `ContentSecurityPolicyManager` | CSP headers with nonce support |
| `CorsManager` | CORS header management |
| `CookieHardening` | Secure, HttpOnly, SameSite cookie flags |
| `DisableXmlRpc` | Disables XML-RPC entirely |
| `DisableFileEditor` | Disables the theme/plugin file editor |
| `DisablePublicCron` | Disables `wp-cron.php` public access |
| `HideWordPressVersion` | Removes WP version from output |
| `DisableUserEnumeration` | Blocks `?author=N` enumeration |
| `DatabaseHardening` | Changes default table prefix, disables DB debug |
| `PhpConfigHardening` | Secures PHP runtime settings |
| `UploadSecurity` | Restricts upload MIME types and validates files |
| `DirectoryProtection` | Adds index files and disables directory listing |
| `CapabilityHardening` | Prevents capability self-escalation |
| `PasswordPolicy` | Enforces password complexity requirements |
| `CommentSpamProtection` | Filters spam comments |
| `AutoUpdatePolicy` | Configures auto-update behavior |
| `AdminUrlObfuscation` | Obfuscates the admin login URL |
| `RestApiSecurity` | Restricts REST API access for non-authenticated users |
| `RestApiRateLimiter` | Rate-limits REST API requests |
| `SessionManager` | Secure session handling |
| `IPAccessControl` | IP whitelist/blacklist enforcement |
| `FileIntegrityMonitor` | Detects unauthorized file changes |
| `MalwareScanner` | Scans for known malware patterns |
| `LoginAnomalyDetector` | Detects unusual login patterns |
| `SubresourceIntegrity` | Adds SRI hashes to external scripts/styles |
| `SecurityNotifier` | Email alerts on critical security events |
| `SecurityHeadersConfigurator` | Orchestrates all security header rules |
