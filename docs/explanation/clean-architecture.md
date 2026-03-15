# Clean Architecture and DDD in WordPress

## The challenge

WordPress is a monolithic CMS with global functions (`add_action`, `register_post_type`, `get_post_meta`...). Any PHP file can call any WordPress function at any time. This makes it impossible to test business logic in isolation or to swap out the underlying platform.

## The solution: Ports and Adapters

BackTo Framework applies the **Hexagonal Architecture** (Ports and Adapters) pattern to decouple the application from WordPress:

```
           ┌──────────────────┐
           │   Domain Layer   │
           │                  │
           │  Entities        │
           │  Value Objects   │
           │  Contracts       │
           │  Factories       │
           │  Registries      │
           └────────┬─────────┘
                    │ depends on
           ┌────────▼─────────┐
           │ Application Layer│
           │                  │
           │  Orchestrators   │
           │  (RegisterPost   │
           │   Type, etc.)    │
           └────────┬─────────┘
                    │ depends on Port interfaces
           ┌────────▼─────────┐
           │ Infrastructure   │
           │                  │
           │  WP Adapters     │
           │  Repositories    │
           │  DI Config       │
           └──────────────────┘
```

### Ports (Contracts)

Port interfaces define **what** the application needs, without specifying **how**:

- `HookDispatcherInterface` — "I need to register callbacks on named events"
- `PostTypeRegistrarInterface` — "I need to register a content type with a key and args"
- `FileLocatorInterface` — "I need to find a file by its media ID"

These live in each module's `Contracts/` directory.

### Adapters (Infrastructure)

WordPress adapters implement ports using actual WP functions:

- `WordPressHookDispatcher` — calls `add_action()`, `add_filter()`
- `WordPressPostTypeRegistrar` — calls `register_post_type()`
- `WordPressFileLocator` — calls `get_attached_file()`

These live in each module's `Infrastructure/` directory.

### Why it matters

1. **Testability**: Orchestrators can be tested with mock ports — no WordPress bootstrap needed.
2. **Substitutability**: Replace `WordPressPostTypeRegistrar` with a `DrupalPostTypeRegistrar` (hypothetically) without touching the application layer.
3. **Clarity**: Each class has a single reason to change. The domain doesn't change when WordPress updates its API.

## DDD-lite patterns

The framework uses Domain-Driven Design patterns pragmatically:

### Entities

`PostType`, `Taxonomy`, `PostMetaStructure` are domain entities with identity (their key) and behavior (fluent setters, validation).

### Value Objects

`PostMeta` represents an immutable meta value. `PostMetaType` enumerates valid types.

### Factories

`PostTypeFactory`, `TaxonomyFactory` encapsulate creation logic (default args, hierarchical support, editor support).

### Registries

`PostTypeRegistry`, `TaxonomyRegistry`, `BlockStyleRegistry` are in-memory collections. They implement `RegistryInterface` and are auto-marked as public in the DI container.

### Repositories

`PostRepository`, `TermRepository`, `PostMetaRepository` provide data access. They intentionally live in the infrastructure boundary — these are the only classes that query WordPress directly.

## What is NOT decoupled (and why)

Some classes still call WordPress functions directly:

- **Admin classes** (`AddReusableBlockMenu`, `AddMenuForEditors`): Their callback methods call `add_menu_page()`, `remove_submenu_page()`, etc. These are leaf nodes — they don't contain business logic worth abstracting.
- **I18n classes**: Their callbacks call `load_theme_textdomain()` / `load_plugin_textdomain()`. These are WordPress-specific by nature.
- **SEO providers**: They read from WordPress options and post meta. This is their purpose — they *are* the WordPress adapter for SEO data.

The hook *registration* (`add_action('admin_menu', ...)`) is always decoupled via `HookDispatcherInterface`. Only the callback *implementations* may call WP functions when they serve as infrastructure endpoints.
