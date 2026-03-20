# Ports & adapters

The bundle applies the same hexagonal architecture as the rest of the framework. `BlockRegistrarInterface` and `BlockStyleRegistrarInterface` isolate business logic from WordPress functions. The adapters in `Infrastructure/` (`WordPressBlockRegistrar`, `WordPressBlockStyleRegistrar`) make the actual WordPress API calls.
