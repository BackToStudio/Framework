# Why a PSR-18 HTTP client in a WordPress framework?

WordPress provides `wp_remote_get()` and `wp_remote_post()`, but calling them directly in domain code creates tight coupling. Business logic becomes untestable without a running WordPress instance, and swapping the HTTP transport (for testing, queuing, or non-WordPress contexts) is impossible.

The HTTP module solves this by placing a **port interface** between domain code and WordPress's HTTP API, following the Hexagonal Architecture pattern used throughout the framework.
