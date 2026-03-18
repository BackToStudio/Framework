# Queue

Async job queue system with background processing via WP-Cron, configurable retries, recurring jobs, and group-based sequential processing.

## Classes

| Class | Role |
|-------|------|
| `Entity\Job` | Domain entity holding job state with invariant guards and domain methods |
| `Entity\JobStatus` | Enum: Pending, Running, Completed, Failed, Cancelled |
| `Factory\JobFactory` | Creates `Job` entities and hydrates from DB rows |
| `QueueRegistry` | Collects registered job handlers |
| `QueueDispatcher` | Dispatches jobs: `dispatch()`, `dispatchUnique()`, `schedule()` |
| `QueueWorker` | Processes jobs by batch with retry and recurring rescheduling |
| `RegisterQueue` | Application orchestrator (WP-Cron, activation, deactivation) |
| `Contracts\JobInterface` | Interface for job handler definitions |
| `Contracts\QueueDispatcherInterface` | Port for dispatching jobs |
| `Contracts\QueueRepositoryInterface` | Port for job persistence |
| `Contracts\QueueRegistryInterface` | Port for registry access |
| `Infrastructure\WordPressQueueRepository` | WP adapter (custom `wp_backto_queue_jobs` table) |

## Job entity

### Invariant guards

Setters reject invalid values with `\InvalidArgumentException`:

| Setter | Guard |
|--------|-------|
| `setAttempts(int $n)` | `$n >= 0` |
| `setMaxRetries(int $n)` | `$n >= 0` |
| `setIntervalSeconds(int $n)` | `$n >= 0` |

### Domain methods (state transitions)

The `Job` entity implements a state machine. Transitions that violate the allowed flow throw `\LogicException`.

| Method | From | To | Side effects |
|--------|------|----|-------------|
| `markAsRunning(string $claimToken)` | Pending, Failed | Running | Sets `claimedAt`, stores claim token |
| `markAsCompleted()` | Running | Completed | Sets `completedAt`, clears claim token |
| `markAsFailed(string $error)` | Running | Failed | Sets `lastError`, increments attempts, clears claim token |
| `cancel()` | any except Completed | Cancelled | Clears claim token |
| `reschedule(DateTimeImmutable $at)` | Completed, Failed | Pending | Sets `scheduledAt`, clears `completedAt` and `claimedAt` |
| `incrementAttempts()` | any | — | `attempts++` |

### Query methods

| Method | Returns | Description |
|--------|---------|-------------|
| `isReady()` | `bool` | Pending and scheduled time has passed (or no schedule) |
| `canRetry()` | `bool` | `attempts < maxRetries` |
| `isRecurring()` | `bool` | `intervalSeconds > 0` |

### State diagram

```
Pending ──markAsRunning()──→ Running ──markAsCompleted()──→ Completed
  ↑                            │                              │
  │                    markAsFailed()                  reschedule()
  │                            ↓                              │
  └────reschedule()────── Failed ←─────────────────────────────┘

Any (except Completed) ──cancel()──→ Cancelled
```

## Contracts

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

## Autoconfiguration

Classes implementing `JobInterface` are automatically tagged `wordpress.queue_job` and registered in the `QueueRegistry` via the `RegisterQueuePass` compiler pass.

## Database schema

The `wp_backto_queue_jobs` table is created on plugin activation:

| Column | Type | Description |
|--------|------|-------------|
| `id` | `BIGINT AUTO_INCREMENT` | Primary key |
| `job_key` | `VARCHAR(255)` | Job handler identifier |
| `payload` | `LONGTEXT` | JSON-encoded job data |
| `status` | `VARCHAR(20)` | pending, running, completed, failed, cancelled |
| `group_name` | `VARCHAR(100)` | Processing group |
| `attempts` | `INT` | Number of execution attempts |
| `max_retries` | `INT` | Maximum allowed retries |
| `error_message` | `TEXT` | Last error message (nullable) |
| `scheduled_at` | `DATETIME` | When the job should be processed |
| `claimed_at` | `DATETIME` | When a worker claimed the job (nullable) |
| `completed_at` | `DATETIME` | When the job finished (nullable) |
| `recurring_interval` | `INT` | Seconds between recurring executions (nullable) |
| `created_at` | `DATETIME` | Row creation timestamp |
| `updated_at` | `DATETIME` | Last modification timestamp |

### Indexes

| Index | Columns | Purpose |
|-------|---------|---------|
| `idx_status_group_scheduled` | `status`, `group_name`, `scheduled_at` | Claim next pending job |
| `idx_job_key_status` | `job_key`, `status` | Unique job deduplication |
| `idx_status_claimed` | `status`, `claimed_at` | Rescue stuck jobs |
| `idx_status_completed` | `status`, `completed_at` | Cleanup completed jobs |

## Cron events

| Event | Schedule | Handler |
|-------|----------|---------|
| `backto_queue_process` | Every minute | `QueueWorker::processAll()` |
| `backto_queue_rescue` | Hourly | `QueueWorker::rescueStuck()` |
| `backto_queue_cleanup` | Daily | `QueueWorker::cleanup()` |
