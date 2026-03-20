# Kernel lifecycle

The `PluginKernel` follows a three-phase lifecycle:

1. **Construction** — The kernel receives the environment name (`production`, `development`, etc.) and the debug flag.
2. **Configuration** — `setProjectDir()` and `setTextDomain()` set the DI container parameters. Extensions are registered via `addExtension()`.
3. **Boot** — `boot()` creates a `ContainerBuilder`, loads the service configuration from `Resources/config/services.php`, compiles the container, and executes all registered hooks.
