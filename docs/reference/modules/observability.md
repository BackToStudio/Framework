# Observability

Logging, error handling, health checks, and performance monitoring.

## Classes

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

## Contracts

### `LoggerInterface`

PSR-3 compatible logger port interface.

```php
interface LoggerInterface
{
    public function emergency(string $message, array $context = []): void;
    public function alert(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function notice(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
    public function log(mixed $level, string $message, array $context = []): void;
}
```

### `ErrorHandlerInterface`

Error boundary pattern — catches exceptions and delegates to logger.

```php
interface ErrorHandlerInterface
{
    public function handle(\Throwable $exception, array $context = []): void;

    /**
     * @template T
     * @param callable(): T $callback
     * @param T $fallback
     * @return T
     */
    public function capture(callable $callback, mixed $fallback = null): mixed;
}
```

### `HealthCheckInterface`

Verifies that a service or subsystem is operational.

```php
interface HealthCheckInterface
{
    public function getName(): string;
    public function check(): HealthCheckResult;
}
```

### `HealthCheckResult`

Value object with named constructors:

```php
HealthCheckResult::healthy(string $message, array $metadata = []);
HealthCheckResult::degraded(string $message, array $metadata = []);
HealthCheckResult::unhealthy(string $message, array $metadata = []);
```

Methods: `getStatus()`, `getMessage()`, `getMetadata()`, `isHealthy()`, `toArray()`.

### `PerformanceCollectorInterface`

Collects performance metrics with nanosecond-precision timing.

```php
interface PerformanceCollectorInterface
{
    public function startTimer(string $name): void;
    public function stopTimer(string $name): float; // ms
    public function increment(string $name): void;
    /** @return array<string, array{count: int, total_ms: float, avg_ms: float}> */
    public function getMetrics(): array;
}
```
