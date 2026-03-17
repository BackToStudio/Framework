# Contracts and Interfaces Reference

This page documents shared, cross-cutting contracts. Module-specific contracts are documented in their respective [module reference pages](modules.md).

## Hook contracts

### `HookInterface`

Marker interface. All hook services must implement this.

```php
interface HookInterface {}
```

### `Hooks` extends `HookInterface`

Front-end hooks (always executed).

```php
interface Hooks extends HookInterface
{
    public function hooks(): void;
}
```

### `AdminHooks` extends `HookInterface`

Admin-only hooks (executed when `is_admin()` is true).

```php
interface AdminHooks extends HookInterface
{
    public function hooks(): void;
}
```

### `ActivationHooks` extends `HookInterface`

Plugin activation callback. Only effective when a plugin file is set on the `HookRegistry`.

```php
interface ActivationHooks extends HookInterface
{
    public function activate();
}
```

### `DeactivationHooks` extends `HookInterface`

Plugin deactivation callback.

```php
interface DeactivationHooks extends HookInterface
{
    public function deactivate();
}
```

## Configuration contracts

### `ModuleConfiguratorInterface`

Contract for module configurators used in `config/*.php` files. Implementations provide a fluent API for setting module parameters without exposing internal key names.

```php
interface ModuleConfiguratorInterface
{
    /** @return array<string, mixed> */
    public function toParameters(): array;
}
```

Implementations: [`AssetsConfigurator`](modules/assets.md#configuration), [`CacheConfigurator`](modules/cache.md#configuration), [`ObservabilityConfigurator`](modules/observability.md#configuration), [`PerformanceConfigurator`](modules/performance.md#configuration), [`RestApiConfigurator`](modules/rest-api.md#configuration), [`SecurityConfigurator`](modules/security.md#configuration), [`SeoConfigurator`](modules/seo.md#configuration).

## Queue contracts

### `JobInterface`

Defines a job handler that can be dispatched to the queue.

```php
interface JobInterface
{
    public function getKey(): string;
    public function getLabel(): string;
    public function getGroup(): string;
    public function getMaxRetries(): int;
    public function handle(array $payload): void;
}
```

### `QueueDispatcherInterface`

Port interface for dispatching jobs.

```php
interface QueueDispatcherInterface
{
    public function dispatch(string $jobKey, array $payload = [], int $delay = 0, string $group = 'default'): int;
    public function dispatchUnique(string $jobKey, array $payload = [], int $delay = 0, string $group = 'default'): ?int;
    public function schedule(string $jobKey, int $intervalSeconds, array $payload = [], string $group = 'default'): int;
}
```

### `QueueRepositoryInterface`

Port interface for queue job persistence.

```php
interface QueueRepositoryInterface
{
    public function createTable(): void;
    public function dropTable(): void;
    public function enqueue(Job $job): int;
    public function claimNextPending(string $group = 'default'): ?Job;
    public function markCompleted(int $jobId): void;
    public function markFailed(int $jobId, string $errorMessage): void;
    public function release(int $jobId): void;
    public function find(int $jobId): ?Job;
    public function findByStatus(JobStatus $status, int $limit = 20, int $offset = 0): array;
    public function countByStatus(JobStatus $status): int;
    public function cleanup(int $olderThanSeconds = 86400): int;
    public function cancel(int $jobId): void;
    public function rescueStuck(int $timeoutSeconds = 300): int;
}
```

### `QueueRegistryInterface`

```php
interface QueueRegistryInterface
{
    public function add(JobInterface $job): self;
    /** @return JobInterface[] */
    public function getJobs(): array;
    public function get(string $key): ?JobInterface;
}
```

## Registry contracts

### `RegistryInterface`

Marker interface. All registry services are automatically set as public in the DI container.

```php
interface RegistryInterface {}
```
