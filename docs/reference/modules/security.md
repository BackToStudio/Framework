# Security

Comprehensive WordPress security hardening with autoconfigured rules. Configure via `config/security.php` using the fluent `SecurityConfigurator`.

## Configuration

Security parameters are managed by `SecurityConfiguration` and overridden via the fluent `SecurityConfigurator` in `config/security.php`:

```php
<?php

use BackTo\Framework\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        ->passwordMinLength(16)
        ->twoFactorEnabled(true)
        ->twoFactorIssuer('MonApp');
};
```

| Parameter | Default | Configurator method |
|-----------|---------|---------------------|
| `security.headers_enabled` | `true` | `headersEnabled(bool)` |
| `security.xmlrpc_disabled` | `true` | `xmlrpcDisabled(bool)` |
| `security.hide_version` | `true` | `hideVersion(bool)` |
| `security.csp_report_only` | `false` | `cspReportOnly(bool)` |
| `security.password_min_length` | `12` | `passwordMinLength(int)` |
| `security.max_concurrent_sessions` | `1` | `maxConcurrentSessions(int)` |
| `security.rest_api_require_auth` | `true` | `restApiRequireAuth(bool)` |
| `security.disable_file_editor` | `true` | `disableFileEditor(bool)` |
| `security.two_factor_enabled` | `false` | `twoFactorEnabled(bool)` |
| `security.two_factor_issuer` | `'WordPress'` | `twoFactorIssuer(string)` |

## Contracts

### `SecurityRuleInterface` extends `HookInterface`

Marker interface for security rules auto-registered in the `SecurityRuleRegistry`.

```php
interface SecurityRuleInterface extends HookInterface
{
    public function getName(): string;
}
```

### `InputSanitizerInterface`

Port interface for input sanitization.

```php
interface InputSanitizerInterface
{
    public function sanitizeText(string $input): string;
    public function sanitizeEmail(string $input): string;
    public function sanitizeUrl(string $input): string;
    public function sanitizeFileName(string $input): string;
    public function sanitizeHtml(string $input, array $allowedHtml = []): string;
    public function sanitizeTextarea(string $input): string;
    public function sanitizeKey(string $input): string;
}
```

### `OutputEscaperInterface`

Port interface for output escaping.

```php
interface OutputEscaperInterface
{
    public function html(string $input): string;
    public function attr(string $input): string;
    public function url(string $input): string;
    public function js(string $input): string;
    public function textarea(string $input): string;
}
```

### `NonceManagerInterface`

Port interface for WordPress nonce (CSRF) management.

```php
interface NonceManagerInterface
{
    public function create(string $action): string;
    public function verify(string $nonce, string $action): bool;
    public function getFieldName(): string;
}
```

### `LoginThrottleInterface`

Port interface for login attempt throttling.

```php
interface LoginThrottleInterface
{
    public function recordFailedAttempt(string $ip): void;
    public function getFailedAttempts(string $ip): int;
    public function isLocked(string $ip): bool;
    public function reset(string $ip): void;
    public function getLockoutRemainingSeconds(string $ip): int;
    public function recordFailedAccountAttempt(string $username): void;
    public function isAccountLocked(string $username): bool;
    public function getAccountLockoutRemainingSeconds(string $username): int;
    public function resetAccount(string $username): void;
}
```

### `ContentSecurityPolicyInterface`

Port interface for CSP management.

```php
interface ContentSecurityPolicyInterface
{
    public function addDirective(string $directive, string|array $value): self;
    public function getDirectives(): array;
    public function buildHeaderValue(): string;
    public function generateNonce(): string;
    public function getNonce(): string;
}
```

### `CorsManagerInterface`

Port interface for CORS configuration.

```php
interface CorsManagerInterface
{
    public function addAllowedOrigin(string|array $origin): self;
    public function addAllowedMethod(string|array $method): self;
    public function addAllowedHeader(string|array $header): self;
    public function setAllowCredentials(bool $allow): self;
    public function setMaxAge(int $seconds): self;
    public function buildHeaders(string $requestOrigin): array;
}
```

### `IPAccessControlInterface`

Port interface for IP-based access control.

```php
interface IPAccessControlInterface
{
    public function addToWhitelist(string $ip): self;
    public function addToBlacklist(string $ip): self;
    public function isAllowed(string $ip): bool;
    public function isBlocked(string $ip): bool;
    public function getWhitelist(): array;
    public function getBlacklist(): array;
}
```

### `AuditLogRepositoryInterface`

Port interface for persisting security audit events.

```php
interface AuditLogRepositoryInterface
{
    public function store(string $event, string $severity, array $context): void;
    public function getEvents(array $filters = [], int $limit = 100, int $offset = 0): array;
    public function purge(int $olderThanDays): int;
}
```

### `FileIntegrityRepositoryInterface`

Port interface for file integrity baselines.

```php
interface FileIntegrityRepositoryInterface
{
    public function storeBaseline(array $hashes): void;
    public function getBaseline(): ?array;
    public function hasBaseline(): bool;
    public function getBaselineTimestamp(): ?int;
}
```

### `RateLimiterRepositoryInterface`

Port interface for rate limiter state.

```php
interface RateLimiterRepositoryInterface
{
    public function increment(string $key, int $windowSeconds): int;
    public function getHits(string $key): int;
    public function getTtl(string $key): int;
}
```

### `SecurityNotifierInterface`

Port interface for security notifications.

```php
interface SecurityNotifierInterface
{
    public function notify(string $event, string $severity, array $context): void;
    public function setRecipients(array $emails): self;
    public function getRecipients(): array;
}
```

### `LoginLocationRepositoryInterface`

Port interface for login location tracking.

```php
interface LoginLocationRepositoryInterface
{
    public function recordLogin(int $userId, array $locationData): void;
    public function getLoginHistory(int $userId, int $limit = 10): array;
    public function getKnownCountries(int $userId): array;
}
```

### `SubresourceIntegrityInterface`

Port interface for SRI hash management.

```php
interface SubresourceIntegrityInterface
{
    public function registerHash(string $handle, string $hash): self;
    public function getHash(string $handle): ?string;
}
```

### `TotpProviderInterface`

TOTP operations (RFC 6238).

```php
interface TotpProviderInterface
{
    public function generateSecret(int $length = 20): string;
    public function generateCode(string $secret, ?int $timestamp = null): string;
    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool;
    public function getProvisioningUri(string $secret, string $accountName, string $issuer): string;
}
```

### `BackupCodeManagerInterface`

Backup code generation and verification.

```php
interface BackupCodeManagerInterface
{
    public function generate(int $count = 8): array;
    public function hash(string $code): string;
    public function verify(string $code, array $hashedCodes): bool;
    public function findMatchingIndex(string $code, array $hashedCodes): ?int;
}
```

### `TwoFactorRepositoryInterface`

Port interface for 2FA user settings.

```php
interface TwoFactorRepositoryInterface
{
    public function isEnabled(int $userId): bool;
    public function enable(int $userId): void;
    public function disable(int $userId): void;
    public function getSecret(int $userId): ?string;
    public function setSecret(int $userId, string $secret): void;
    public function deleteSecret(int $userId): void;
    public function getBackupCodes(int $userId): array;
    public function setBackupCodes(int $userId, array $hashedCodes): void;
    public function deleteBackupCodes(int $userId): void;
}
```
