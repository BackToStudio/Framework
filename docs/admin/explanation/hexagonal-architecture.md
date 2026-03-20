# Hexagonal architecture

The Admin bundle follows the ports & adapters pattern used throughout the framework. The interfaces `AdminPageRegistrarInterface` and `CapabilityManagerInterface` are **ports** — domain contracts that define what the bundle needs from the outside world. The classes in `Infrastructure/` (`WordPressAdminPageRegistrar`, `WordPressCapabilityManager`) are **adapters** that call WordPress functions directly.

This separation allows testing business logic without loading WordPress. In tests, the ports are replaced by mocks or stubs.
