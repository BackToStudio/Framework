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

WordPress admin customizations.

| Class | Role |
|-------|------|
| `AddReusableBlockMenu` | Adds reusable blocks menu page |
| `AddMenuForEditors` | Grants editor role theme options access |

## Compose

Framework kernel and container management.

| Class | Role |
|-------|------|
| `AbstractKernel` | Base class for theme/plugin kernels |
| `WordPressContainer` | Trait — container lifecycle (build, cache, dump, load) |
| `TextDomain` | Trait — text domain management |
| `DependencyInjection\WordPressExtension` | Autoconfiguration + compiler passes + port bindings |
