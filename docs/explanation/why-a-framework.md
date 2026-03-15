# Why a WordPress Framework?

## The problem

WordPress development traditionally relies on procedural code scattered across `functions.php`, loose `add_action` / `add_filter` calls, and global state. This approach works for small sites but breaks down as projects grow:

- **No dependency injection**: Services instantiate their own dependencies, making testing and substitution impossible.
- **No separation of concerns**: Business logic, WordPress API calls, and presentation are entangled.
- **No service discovery**: Registering a custom post type requires manually calling `register_post_type()` in the right hook, in the right file, at the right priority.
- **No caching of configuration**: Each request re-executes the same registration logic.

## What BackTo Framework solves

### Dependency Injection

The framework uses Symfony's DI container (scoped under `BackToVendor\` to avoid conflicts). Services are autowired and autoconfigured — you define a class, implement the right interface, and the framework handles the rest.

### Convention over configuration

Implementing `PostTypeInterface` is enough to register a custom post type. No hooks, no manual calls. The framework's compiler passes collect tagged services and wire them to the correct registries automatically.

### Compiled container

The DI container is compiled to a PHP file (`var/container.php`) and cached. Subsequent requests load a single PHP file instead of re-parsing all service definitions.

### Testability

Domain entities, factories, and registries have zero WordPress dependencies. Orchestrators depend on port interfaces (e.g. `PostTypeRegistrarInterface`), not on `register_post_type()`. This means you can unit test your business logic without WordPress loaded.

## When to use it

BackTo Framework is designed for **professional WordPress projects** where you need:

- Multiple custom post types and taxonomies
- Structured, maintainable codebase
- Unit tests without bootstrapping WordPress
- Team collaboration with clear architectural boundaries

## When not to use it

For simple blogs or single-page themes with no custom post types, the framework's structure adds unnecessary complexity. Use it when the project's complexity justifies the abstraction.
