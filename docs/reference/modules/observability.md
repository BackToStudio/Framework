# Observability

Logging, error handling, health checks, and performance monitoring.

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
