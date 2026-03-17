# Modules Reference

## PostType

Registers WordPress custom post types.

| Class | Role |
|-------|------|
| `Entity\PostType` | Domain entity holding key + args |
| `PostTypeFactory` | Creates `PostType` with default args |
| `PostTypeRegistry` | Collects registered post types |
| `RegisterPostType` | Application orchestrator (hooks into `init`) |
| `Contracts\PostTypeInterface` | Interface for post type definitions |
| `Contracts\PostTypeRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressPostTypeRegistrar` | WP adapter |
| `Repository\PostRepository` | Queries posts via `WP_Query` |

## Taxonomy

Registers WordPress custom taxonomies.

| Class | Role |
|-------|------|
| `Entity\Taxonomy` | Domain entity holding key + args + post types |
| `TaxonomyFactory` | Creates `Taxonomy` with default args |
| `TaxonomyRegistry` | Collects registered taxonomies |
| `RegisterTaxonomy` | Application orchestrator (hooks into `init`) |
| `Contracts\TaxonomyInterface` | Interface for taxonomy definitions |
| `Contracts\TaxonomyRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressTaxonomyRegistrar` | WP adapter |
| `Repository\TermRepository` | Queries terms via `get_terms` |

## Blocks

Registers WordPress block styles.

| Class | Role |
|-------|------|
| `CustomBlockStyle` | Abstract base for block style definitions |
| `BlockStyleRegistry` | Collects registered block styles |
| `BlockRegistry` | Collects registered blocks |
| `RegisterBlockStyles` | Application orchestrator (hooks into `after_setup_theme`) |
| `Contracts\BlockStyleRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressBlockStyleRegistrar` | WP adapter |
| `Actions\ReplaceImgBlockBySvgBlock` | Replaces `<img>` with inline SVG in image blocks |

## PostMeta

Registers WordPress post meta fields.

| Class | Role |
|-------|------|
| `Entity\PostMetaStructure` | Domain entity (fluent API for meta definition) |
| `Entity\PostMeta` | Value object for a single meta entry |
| `Entity\PostMetaType` | Enum-like class for meta types |
| `PostMetaStructureRegistry` | Collects registered meta structures |
| `RegisterPostMetaStructure` | Application orchestrator (hooks into `init`) |
| `Contracts\PostMetaStructureInterface` | Interface for meta definitions |
| `Contracts\PostMetaRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressPostMetaRegistrar` | WP adapter |
| `Repository\PostMetaRepository` | Queries post meta via `get_post_meta` |
| `Factory\PostMetaFactory` | Creates `PostMeta` from raw data |
| `Factory\PostMetaStructureFactory` | Creates `PostMetaStructure` entities |

## Cache

PSR-16 SimpleCache implementation with 3 strategies.

| Class | Role |
|-------|------|
| `Strategy\MemoryCache` | In-memory array (request-scoped) |
| `Strategy\TransientCache` | WordPress transients (database) |
| `Strategy\FilesystemCache` | Disk-based via Symfony Filesystem |
| `Strategy\AbstractCache` | Shared key validation + TTL conversion |
| `Contracts\CacheInterface` | PSR-16 compatible interface |
| `Contracts\InvalidArgumentException` | Exception for invalid cache keys |

## Seo

Unified SEO plugin integration.

| Class | Role |
|-------|------|
| `SeoManager` | Resolves first active provider, provides shortcuts |
| `Provider\YoastProvider` | Yoast SEO adapter |
| `Provider\SeoPressProvider` | SEOPress adapter |
| `Contracts\SeoProviderInterface` | Unified provider interface |
| `Contracts\SocialLinksProviderInterface` | Social links methods |
| `Contracts\MetaProviderInterface` | Meta data methods |
| `Actions\CleanYoastFootprint` | Removes Yoast debug output |
| `Hooks\AddSocialLinksToTimberContext` | Injects social links into Timber |

## Hooks

Central hook orchestration.

| Class | Role |
|-------|------|
| `HookRegistry` | Collects and runs all hook services |
| `Infrastructure\WordPressHookDispatcher` | WP adapter for `add_action`/`add_filter` |

## Assets

Media and SVG utilities.

| Class | Role |
|-------|------|
| `SvgFactory` | Loads SVG from ID, URL, or path |
| `ReplaceImgTagBySvgTag` | HTML tag replacement utility |
| `Contracts\FileLocatorInterface` | Port for WP media functions |
| `Infrastructure\WordPressFileLocator` | WP adapter |

## Admin

WordPress admin page management.

| Class | Role |
|-------|------|
| `AdminPageRegistry` | Collects registered admin pages |
| `RegisterAdminPage` | Application orchestrator (hooks into `admin_menu`) |
| `Contracts\AdminPageInterface` | Interface for admin page definitions |
| `Contracts\AdminPageRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressAdminPageRegistrar` | WP adapter (`add_menu_page`/`add_submenu_page`) |
| `AddReusableBlockMenu` | Adds reusable blocks menu page |
| `AddMenuForEditors` | Grants editor role theme options access |

## Options

WordPress `wp_options` abstraction.

| Class | Role |
|-------|------|
| `Contracts\OptionsRepositoryInterface` | Port interface (get, update, delete, exists) |
| `Infrastructure\WordPressOptionsRepository` | WP adapter |

## RestApi

REST API route management with autoconfiguration.

| Class | Role |
|-------|------|
| `RestRouteRegistry` | Collects registered REST routes |
| `RegisterRestRoute` | Application orchestrator (hooks into `rest_api_init`) |
| `Contracts\RestRouteInterface` | Interface for route definitions |
| `Contracts\RestRouteRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressRestRouteRegistrar` | WP adapter (`register_rest_route`) |

## Observability

Logging, error handling, health checks, and performance monitoring.

| Class | Role |
|-------|------|
| `Contracts\LoggerInterface` | PSR-3 compatible logger port |
| `Infrastructure\WordPressLogger` | WP adapter (error_log with structured formatting) |
| `Infrastructure\NullLogger` | No-op logger for testing |
| `ErrorHandler` | Error boundary with `capture()` pattern |
| `Contracts\ErrorHandlerInterface` | Port for error handling |
| `HealthCheckRegistry` | Collects and runs health checks |
| `Contracts\HealthCheckInterface` | Port for health check definitions |
| `Contracts\HealthCheckResult` | Value object (healthy/degraded/unhealthy) |
| `HealthCheck\ContainerHealthCheck` | Verifies DI container state |
| `HealthCheck\CacheHealthCheck` | Verifies cache operations |
| `PerformanceCollector` | In-memory timing and counters |
| `ObservableHookDispatcher` | Decorator adding instrumentation to hook dispatch |

## Cli

WP-CLI scaffolding commands.

| Class | Role |
|-------|------|
| `Command\AbstractMakeCommand` | Base class for make commands |
| `Command\MakePostTypeCommand` | `wp make:post-type` |
| `Command\MakeTaxonomyCommand` | `wp make:taxonomy` |
| `Command\MakeBlockCommand` | `wp make:block` |
| `Command\MakeHookCommand` | `wp make:hook` |
| `Command\MakeRestRouteCommand` | `wp make:rest-route` |
| `Generator\ClassGenerator` | Template engine for code generation |

## Security

Comprehensive WordPress security hardening with autoconfigured rules. Configure via `config/security.php` using the fluent `SecurityConfigurator`.

| Class | Role |
|-------|------|
| `SecurityConfiguration` | Default parameter values for the Security module |
| `SecurityConfigurator` | Fluent configurator for `config/security.php` (implements `ModuleConfiguratorInterface`) |
| `Contracts\SecurityRuleInterface` | Marker interface for security rules (extends `HookInterface`) |
| `SecurityRuleRegistry` | Collects all registered security rules |
| `DependencyInjection\Compiler\RegisterSecurityRulePass` | Auto-tags `SecurityRuleInterface` services |
| `LoginHardening` | Brute-force throttling (IP + account) with generic error messages |
| `TwoFactor\TwoFactorAuthentication` | TOTP-based 2FA with backup codes |
| `SecurityAuditLogger` | Persistent audit log (logins, role changes, options, plugins) |
| `SecurityNotifier` | Email alerts on critical security events |
| `ContentSecurityPolicyManager` | CSP header management with nonce support |
| `CorsManager` | CORS header configuration |
| `HttpHeadersHardening` | Security headers (X-Frame-Options, HSTS, etc.) |
| `SecurityHeadersConfigurator` | Orchestrates all security header rules |
| `CookieHardening` | Secure, HttpOnly, SameSite cookie flags |
| `DisableXmlRpc` | Disables XML-RPC |
| `DisableFileEditor` | Disables theme/plugin file editor |
| `DisablePublicCron` | Disables public `wp-cron.php` |
| `HideWordPressVersion` | Strips version info from output |
| `DisableUserEnumeration` | Blocks `?author=N` enumeration |
| `DatabaseHardening` | Database security settings |
| `PhpConfigHardening` | PHP runtime security settings |
| `UploadSecurity` | MIME type restrictions and upload validation |
| `DirectoryProtection` | Directory listing prevention |
| `CapabilityHardening` | Prevents capability self-escalation |
| `PasswordPolicy` | Password complexity enforcement |
| `CommentSpamProtection` | Comment spam filtering |
| `AutoUpdatePolicy` | Auto-update configuration |
| `AdminUrlObfuscation` | Login URL obfuscation |
| `RestApiSecurity` | REST API access restriction |
| `RestApiRateLimiter` | REST API rate limiting |
| `SessionManager` | Secure session handling |
| `IPAccessControl` | IP whitelist/blacklist enforcement |
| `FileIntegrityMonitor` | Detects unauthorized file modifications |
| `MalwareScanner` | Malware pattern scanning |
| `LoginAnomalyDetector` | Unusual login pattern detection |
| `SubresourceIntegrity` | SRI hashes for external assets |
| `Infrastructure\WordPressAuditLogRepository` | WP adapter for audit log storage |
| `Infrastructure\WordPressLoginThrottle` | WP adapter for login throttle |
| `Infrastructure\WordPressNonceManager` | WP adapter for nonce operations |
| `Infrastructure\WordPressInputSanitizer` | WP adapter for input sanitization |
| `Infrastructure\WordPressOutputEscaper` | WP adapter for output escaping |
| `Infrastructure\WordPressFileIntegrityRepository` | WP adapter for file integrity baselines |
| `Infrastructure\WordPressRateLimiterRepository` | WP adapter for rate limiter state |
| `Infrastructure\WordPressLoginLocationRepository` | WP adapter for login location history |
| `TwoFactor\TotpProvider` | TOTP code generation/verification (RFC 6238) |
| `TwoFactor\BackupCodeManager` | Backup code generation and verification |
| `TwoFactor\Base32` | Base32 encoding for TOTP secrets |
| `TwoFactor\Infrastructure\WordPressTwoFactorRepository` | WP adapter for 2FA user settings |
| `RestApi\SecurityAuditLogRoute` | REST endpoint: `GET /backto/v1/security/audit-log` |
| `RestApi\SecurityHealthRoute` | REST endpoint: `GET /backto/v1/security/health` |
| `RestApi\SecurityScanRoute` | REST endpoint: `GET /backto/v1/security/scan` |
| `HealthCheck\SecurityHealthCheck` | Health check: critical rules active, PHP version, file editor |
| `AuditLogAdminPage` | Admin page for viewing audit logs |

## Gdpr

GDPR consent management with cookie-based storage, consent banner, and conditional script loading.

| Class | Role |
|-------|------|
| `Entity\ConsentCategory` | Immutable value object for a consent category |
| `Entity\TrackingScript` | Immutable value object for a tracking script |
| `ConsentCategoryRegistry` | Collects registered consent categories |
| `TrackingScriptRegistry` | Collects registered tracking scripts |
| `RegisterGdpr` | Application orchestrator (hooks into `wp_head` / `wp_footer`) |
| `ConsentBanner` | Renders the consent banner HTML/CSS/JS |
| `Contracts\ConsentCategoryInterface` | Interface for consent categories |
| `Contracts\TrackingScriptInterface` | Interface for tracking scripts |
| `Contracts\ConsentStorageInterface` | Port for reading user consent |
| `Infrastructure\CookieConsentStorage` | Cookie-based adapter |
| `Preset\GoogleTagManagerScript` | GTM preset (container ID) |
| `Preset\GtagLoaderScript` | gtag.js loader preset (tracking ID) |
| `Preset\GoogleAnalyticsScript` | GA4 preset (measurement ID) |
| `Preset\GoogleAdsScript` | Google Ads preset (conversion ID) |
| `Preset\HotjarScript` | Hotjar preset (site ID) |
| `Preset\HubSpotScript` | HubSpot preset (portal ID) |

## Compose

Framework kernel and container management.

| Class | Role |
|-------|------|
| `AbstractKernel` | Base class for theme/plugin kernels |
| `WordPressContainer` | Trait — container lifecycle (build, cache, dump, load) |
| `TextDomain` | Trait — text domain management |
| `DependencyInjection\WordPressExtension` | Autoconfiguration + compiler passes + port bindings |
| `Configuration\FrameworkConfiguration` | Default framework parameters |
