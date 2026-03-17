# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **SEO Schema**: `SchemaRef` value object for `@id` cross-referencing between nodes in a JSON-LD `@graph`
- **SEO Schema**: `Schema::ref()` factory method for creating `@id` references
- **SEO Schema**: `SchemaType::id()` method to set `@id` on any schema node
- **SEO Schema**: Schema validation with `validate()` and `isValid()` methods — checks required properties per Google's rich results guidelines for Article, Product, Event, Course, JobPosting, VideoObject, FAQPage, Review, LocalBusiness, Offer
- **SEO Schema**: `SchemaManager::validate()` to validate all registered schemas at once
- **SEO Schema**: `PostTypeSchemaResolver` for mapping custom post types to schema generators
- **SEO Schema**: `SeoConfig` class to load schema configuration from `config/seo.php` (post type map, disable plugin schema)
- **SEO Schema**: Built-in generators (WebSite, Organization, Article) now use `@id` linking and `Schema::ref()` for a connected graph
- **Documentation**: Schema structured data sections in SEO how-to and reference docs
- **Admin module**: `AdminPageInterface`, `AdminPageRegistry`, `RegisterAdminPage` orchestrator, `WordPressAdminPageRegistrar` adapter
- **Options module**: `OptionsRepositoryInterface` port with `WordPressOptionsRepository` adapter
- **REST API module**: `RestRouteInterface`, `RestRouteRegistry`, `RegisterRestRoute` orchestrator, `WordPressRestRouteRegistrar` adapter
- **Observability module**: PSR-3 compatible `LoggerInterface` with `WordPressLogger` adapter, `ErrorHandler` with error boundary pattern, `HealthCheckRegistry` with `ContainerHealthCheck` and `CacheHealthCheck`, `PerformanceCollector` with high-resolution timing, `ObservableHookDispatcher` decorator
- **CLI scaffolding**: WP-CLI commands `make:post-type`, `make:taxonomy`, `make:block`, `make:hook`, `make:rest-route` with template-based code generation
- **Framework configuration**: `FrameworkConfiguration` with overridable default parameters (cache, SEO, REST API, assets, observability)
- **Documentation**: How-to guides for REST API, Options, CLI scaffolding, Observability, Query Monitor debugging
- PHP 8.4 support in CI pipeline
- CONTRIBUTING.md with development guidelines

### Changed
- `HookDispatcherInterface`: callback parameter now accepts `callable|string` for WordPress compatibility
- `PostTypeInterface`: added `setArgs()` method
- `TaxonomyInterface`: added `setArgs()` and `setPostTypes()` methods
- `PostMetaStructureInterface`: added `getType()` and `setType()` methods
- `BlockStyleInterface`: tightened `getProperties()` return type to `array{name: string, label: string}`
- `ActivationHooks::activate()` and `DeactivationHooks::deactivate()` now require `void` return type
- `WordPressExtension`: registers 10 autoconfigured interfaces and 9 compiler passes
- CI pipeline: removed PHP 8.1, added PHP 8.4, added lint stage (PHPStan + CS-Fixer)

### Fixed
- PHPStan Level 8: reduced baseline from 80 ignored errors to 3 (WordPress API type mismatches only)
- `PostFactory`: fixed fluent chain breaking on `IdInterface` return type
- `TermFactory`: fixed fluent chain + parameter name `$wpPosts` to `$wpTerms`
- `CleanHead`: removed invalid 4th argument from `remove_action` calls
- `LoadMuPluginTextDomain`: removed incorrect 3rd argument from `load_muplugin_textdomain`
- `PostMetaRepository::create()`: fixed return type to `PostMetaInterface`
- `ResolveInstanceOfConditionalPassWithVendorPrefix`: added proper exception imports

### Security
- Cache: added `allowed_classes` parameter to all `unserialize()` calls (TransientCache, FilesystemCache, CompilerPass)
- CLI: input validation against path traversal, code injection, and namespace injection
- CLI: `realpath()` validation for output directory
- CLI: regex whitelist for post-type names, block namespaces, route namespaces
- `PostFactory`: exceptions are now logged instead of silenced
- `WordPressContainer::load()`: error logging includes file and line context

### Removed
- PHP 8.1 CI support (minimum is PHP 8.2 per composer.json)
