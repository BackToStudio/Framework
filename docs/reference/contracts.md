# Contracts and Interfaces Reference

## Hook contracts

### `HookInterface`

Marker interface. All hook services must implement this.

```php
interface HookInterface {}
```

### `Hooks` extends `HookInterface`

Front-end hooks (always executed).

```php
interface Hooks extends HookInterface
{
    public function hooks(): void;
}
```

### `AdminHooks` extends `HookInterface`

Admin-only hooks (executed when `is_admin()` is true).

```php
interface AdminHooks extends HookInterface
{
    public function hooks(): void;
}
```

### `ActivationHooks` extends `HookInterface`

Plugin activation callback. Only effective when a plugin file is set on the `HookRegistry`.

```php
interface ActivationHooks extends HookInterface
{
    public function activate();
}
```

### `DeactivationHooks` extends `HookInterface`

Plugin deactivation callback.

```php
interface DeactivationHooks extends HookInterface
{
    public function deactivate();
}
```

## Entity contracts

### `PostTypeInterface`

```php
interface PostTypeInterface
{
    public function getKey(): ?string;
    public function getArgs(): array;
}
```

### `TaxonomyInterface`

```php
interface TaxonomyInterface
{
    public function getKey(): ?string;
    public function getArgs(): array;
    public function getPostTypes(): array;
}
```

### `BlockInterface`

```php
interface BlockInterface
{
    public function getName(): string;
}
```

### `BlockStyleInterface`

```php
interface BlockStyleInterface
{
    public function getProperties(): array;
    public function getLabel(): string;
    public function getStyleName(): string;
    public function getBlocks(): array;
}
```

### `PostMetaStructureInterface`

Fluent interface for defining meta field structure. Key methods:

| Method | Returns |
|--------|---------|
| `getObjectType()` / `setObjectType()` | `string` / `self` |
| `getMetaKey()` / `setMetaKey()` | `string` / `self` |
| `getType()` / `setType()` | `string` / `self` |
| `getLabel()` / `setLabel()` | `string` / `self` |
| `getDescription()` / `setDescription()` | `string` / `self` |
| `isSingle()` / `setSingle()` | `bool` / `self` |
| `isShowInRest()` / `showInRest()` / `dontShowInRest()` | `bool` / `self` |
| `isRevisionsEnabled()` / `setRevisionsEnabled()` | `bool` / `self` |
| `getDefault()` / `setDefault()` | `mixed` / `self` |
| `getSanitizeCallback()` / `setSanitizeCallback()` | `?callable` / `self` |
| `getAuthCallback()` / `setAuthCallback()` | `?callable` / `self` |

## Port interfaces (Clean Architecture)

### `HookDispatcherInterface`

Abstracts the WordPress hook system.

```php
interface HookDispatcherInterface
{
    public function addAction(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): void;
    public function addFilter(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): void;
    public function removeAction(string $hookName, callable $callback, int $priority = 10): void;
    public function isAdmin(): bool;
    public function registerActivationHook(string $file, callable $callback): void;
    public function registerDeactivationHook(string $file, callable $callback): void;
}
```

### `PostTypeRegistrarInterface`

```php
interface PostTypeRegistrarInterface
{
    public function register(string $key, array $args): void;
    public function exists(string $key): bool;
    public function flushRewriteRules(): void;
}
```

### `TaxonomyRegistrarInterface`

```php
interface TaxonomyRegistrarInterface
{
    public function register(string $key, $objectType, array $args): void;
    public function exists(string $key): bool;
    public function flushRewriteRules(): void;
}
```

### `BlockStyleRegistrarInterface`

```php
interface BlockStyleRegistrarInterface
{
    public function register(string $blockName, array $styleProperties): void;
}
```

### `PostMetaRegistrarInterface`

```php
interface PostMetaRegistrarInterface
{
    public function register(string $postType, string $metaKey, array $args): void;
}
```

### `FileLocatorInterface`

```php
interface FileLocatorInterface
{
    public function getAttachedFile(int $attachmentId): string;
    public function getUploadDir(): array; // {basedir, baseurl}
}
```

## Admin contracts

### `AdminPageInterface` extends `HookInterface`

Represents an admin menu page to be registered.

```php
interface AdminPageInterface extends HookInterface
{
    public function getPageTitle(): string;
    public function getMenuTitle(): string;
    public function getCapability(): string;
    public function getMenuSlug(): string;
    public function getIconUrl(): string;
    public function getPosition(): ?int;
    public function render(): void;
}
```

## Options contracts

### `OptionsRepositoryInterface`

Port interface for WordPress options (`wp_options`).

```php
interface OptionsRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function update(string $key, mixed $value): bool;
    public function delete(string $key): bool;
    public function exists(string $key): bool;
}
```

## REST API contracts

### `RestRouteInterface` extends `HookInterface`

Represents a REST API route to be registered.

```php
interface RestRouteInterface extends HookInterface
{
    public function getNamespace(): string;
    public function getRoute(): string;
    /** @return string[] */
    public function getMethods(): array;
    public function handle(WP_REST_Request $request): WP_REST_Response;
    public function getPermissionCallback(): ?callable;
}
```

## Observability contracts

### `LoggerInterface`

PSR-3 compatible logger port interface.

```php
interface LoggerInterface
{
    public function emergency(string $message, array $context = []): void;
    public function alert(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function notice(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
    public function log(mixed $level, string $message, array $context = []): void;
}
```

### `ErrorHandlerInterface`

Error boundary pattern — catches exceptions and delegates to logger.

```php
interface ErrorHandlerInterface
{
    public function handle(\Throwable $exception, array $context = []): void;

    /**
     * @template T
     * @param callable(): T $callback
     * @param T $fallback
     * @return T
     */
    public function capture(callable $callback, mixed $fallback = null): mixed;
}
```

### `HealthCheckInterface`

Verifies that a service or subsystem is operational.

```php
interface HealthCheckInterface
{
    public function getName(): string;
    public function check(): HealthCheckResult;
}
```

### `HealthCheckResult`

Value object with named constructors:

```php
HealthCheckResult::healthy(string $message, array $metadata = []);
HealthCheckResult::degraded(string $message, array $metadata = []);
HealthCheckResult::unhealthy(string $message, array $metadata = []);
```

Methods: `getStatus()`, `getMessage()`, `getMetadata()`, `isHealthy()`, `toArray()`.

### `PerformanceCollectorInterface`

Collects performance metrics with nanosecond-precision timing.

```php
interface PerformanceCollectorInterface
{
    public function startTimer(string $name): void;
    public function stopTimer(string $name): float; // ms
    public function increment(string $name): void;
    /** @return array<string, array{count: int, total_ms: float, avg_ms: float}> */
    public function getMetrics(): array;
}
```

## GDPR contracts

### `ConsentCategoryInterface`

```php
interface ConsentCategoryInterface
{
    public function getKey(): string;
    public function getLabel(): string;
    public function getDescription(): string;
    public function isRequired(): bool;
}
```

### `TrackingScriptInterface`

```php
interface TrackingScriptInterface
{
    public function getHandle(): string;
    public function getCategoryKey(): string;
    public function getSource(): string;
    public function isInline(): bool;
    public function getLocation(): string;
    public function getPriority(): int;
}
```

### `ConsentStorageInterface`

Port interface for reading user consent state.

```php
interface ConsentStorageInterface
{
    /** @return array<string, bool> */
    public function getConsent(): array;
    public function hasConsent(string $categoryKey): bool;
    public function isConsentGiven(): bool;
}
```

### `ConsentCategoryRegistryInterface`

```php
interface ConsentCategoryRegistryInterface
{
    public function add(ConsentCategoryInterface $category): self;
    /** @return ConsentCategoryInterface[] */
    public function getCategories(): array;
    public function get(string $key): ?ConsentCategoryInterface;
}
```

### `TrackingScriptRegistryInterface`

```php
interface TrackingScriptRegistryInterface
{
    public function add(TrackingScriptInterface $script): self;
    /** @return TrackingScriptInterface[] */
    public function getScripts(): array;
    /** @return TrackingScriptInterface[] */
    public function getScriptsByCategory(string $categoryKey): array;
}
```

## Cache contracts

### `CacheInterface`

PSR-16 SimpleCache compatible. Methods: `get`, `set`, `delete`, `clear`, `has`, `getMultiple`, `setMultiple`, `deleteMultiple`.

See [PSR-16 specification](https://www.php-fig.org/psr/psr-16/).

## SEO contracts

### `SeoProviderInterface` extends `SocialLinksProviderInterface`, `MetaProviderInterface`

```php
interface SeoProviderInterface
{
    public function getName(): string;
    public function isActive(): bool;
    // + all social links + meta methods
}
```

### `SocialLinksProviderInterface`

Methods: `getFacebookUrl`, `getTwitterUrl`, `getInstagramUrl`, `getLinkedInUrl`, `getPinterestUrl`, `getYouTubeUrl`, `getSocialLinks`.

### `MetaProviderInterface`

Methods: `getTitle`, `getDescription`, `getCanonicalUrl`, `getOgTitle`, `getOgDescription`, `getOgImageUrl` (all with optional `?int $postId`).

## Security contracts

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

## Two-Factor Authentication contracts

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

## Registry contracts

### `RegistryInterface`

Marker interface. All registry services are automatically set as public in the DI container.

```php
interface RegistryInterface {}
```
