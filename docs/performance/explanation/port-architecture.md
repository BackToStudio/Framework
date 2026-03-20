# Port architecture


The bundle follows the Hexagonal Architecture pattern used throughout the framework. Three port interfaces (`PageCacheInterface`, `HtmlOptimizerInterface`, `DatabaseOptimizerInterface`) separate domain logic from WordPress-specific implementations. This means:

- Hook classes depend on interfaces, not concrete adapters
- The filesystem page cache can be swapped for Redis or Memcached without changing any hook
- Unit tests can mock the interfaces without WordPress running

The port bindings are registered in `PerformanceExtension`, which maps each interface to its WordPress adapter.
