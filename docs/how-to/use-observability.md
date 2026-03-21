# Use the Observability Module

The Observability module provides logging, error handling, health checks, and performance monitoring.

## Logger

Inject `LoggerInterface` (PSR-3 compatible):

```php
use BackTo\Framework\Observability\Contracts\LoggerInterface;

class MyService
{
    public function __construct(private LoggerInterface $logger) {}

    public function process(): void
    {
        $this->logger->info('Processing started', ['batch_size' => 50]);

        try {
            // ...
        } catch (\Throwable $e) {
            $this->logger->error('Processing failed: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
```

Log levels: `emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug`.

Configure minimum level in `config/services.php`:

```php
$container->parameters()->set('framework.observability.log_level', 'warning');
```

## Error Handler

Use `ErrorHandlerInterface` for error boundaries:

```php
use BackTo\Framework\Observability\Contracts\ErrorHandlerInterface;

class RiskyService
{
    public function __construct(private ErrorHandlerInterface $handler) {}

    public function doSomething(): ?string
    {
        return $this->handler->capture(
            callback: fn () => $this->riskyOperation(),
            fallback: null
        );
    }
}
```

- In **debug mode**: exceptions are re-thrown for immediate visibility
- In **production**: exceptions are logged and the fallback value is returned

## Health Checks

Implement `HealthCheckInterface` to monitor subsystems:

```php
use BackTo\Framework\Observability\Contracts\HealthCheckInterface;
use BackTo\Framework\Observability\Contracts\HealthCheckResult;

class DatabaseHealthCheck implements HealthCheckInterface
{
    public function getName(): string
    {
        return 'database';
    }

    public function check(): HealthCheckResult
    {
        global $wpdb;

        $result = $wpdb->get_var('SELECT 1');

        if ($result === '1') {
            return HealthCheckResult::healthy('Database responding');
        }

        return HealthCheckResult::unhealthy('Database not responding');
    }
}
```

Health checks are autoconfigured with the `wordpress.health_check` tag.

Query all checks via `HealthCheckRegistry`:

```php
$registry = $container->get(HealthCheckRegistry::class);
$results = $registry->toArray();
// ['database' => ['status' => 'healthy', 'message' => '...', 'metadata' => [...]]]
```

## Performance Collector

Track execution timing:

```php
use BackTo\Framework\Observability\Contracts\PerformanceCollectorInterface;

class ImportService
{
    public function __construct(private PerformanceCollectorInterface $collector) {}

    public function import(): void
    {
        $this->collector->startTimer('import.fetch');
        $data = $this->fetchData();
        $this->collector->stopTimer('import.fetch');

        $this->collector->startTimer('import.process');
        $this->processData($data);
        $this->collector->stopTimer('import.process');
    }
}
```

Retrieve metrics: `$collector->getMetrics()` returns count, total_ms, avg_ms per operation.

## Observable Hook Dispatcher

`ObservableHookDispatcher` is a decorator that logs hook registrations and counts actions/filters. Activate it by swapping the port binding in your `config/services.php` if needed.

## Built-in health checks

| Check | What it monitors |
|-------|-----------------|
| `ContainerHealthCheck` | DI container compiled and fresh |
| `CacheHealthCheck` | Cache read/write operational |
| `DatabaseHealthCheck` | DB connectivity, query performance, table existence |
| `SmtpHealthCheck` | Email delivery via wp_mail() (rate-limited, cached 1h) |
| `MonitorQueueHealth` | Queue pending/running/failed job counts |
| `SecurityHealthCheck` | Security rules active, file editor, PHP version |
