# How to Use the Async Queue System

The Queue module provides a background job processing system for WordPress, similar to Action Scheduler. Jobs are stored in a custom database table and processed via WP-Cron.

## Define a job handler

Create a class that implements `JobInterface`:

```php
<?php

namespace MyPlugin\Queue;

use BackTo\Framework\Queue\Contracts\JobInterface;

class SendWelcomeEmail implements JobInterface
{
    public function getKey(): string
    {
        return 'send_welcome_email';
    }

    public function getLabel(): string
    {
        return 'Send Welcome Email';
    }

    public function getGroup(): string
    {
        return 'emails';
    }

    public function getMaxRetries(): int
    {
        return 3;
    }

    public function handle(array $payload): void
    {
        wp_mail(
            $payload['to'],
            $payload['subject'],
            $payload['body']
        );
    }
}
```

The framework auto-discovers any class implementing `JobInterface`, tags it with `wordpress.queue_job`, and registers it in the `QueueRegistry`.

## Dispatch a job

Inject `QueueDispatcherInterface` in your service and call `dispatch()`:

```php
use BackTo\Framework\Queue\Contracts\QueueDispatcherInterface;

class UserRegistrationService
{
    public function __construct(private QueueDispatcherInterface $dispatcher)
    {
    }

    public function register(string $email): void
    {
        // ... create user ...

        $this->dispatcher->dispatch('send_welcome_email', [
            'to' => $email,
            'subject' => 'Welcome!',
            'body' => 'Thanks for signing up.',
        ]);
    }
}
```

The job is enqueued immediately and processed on the next WP-Cron tick (every minute).

## Dispatch with a delay

Delay execution by a number of seconds:

```php
// Process in 5 minutes
$this->dispatcher->dispatch('send_welcome_email', $payload, 300);
```

## Dispatch a unique job

Prevent duplicate jobs with identical key and payload:

```php
$id = $this->dispatcher->dispatchUnique('sync_inventory', ['sku' => 'ABC123']);

// Returns null if a pending job with the same key + payload already exists
if ($id === null) {
    // Duplicate skipped
}
```

## Schedule a recurring job

Create a job that re-enqueues itself after each successful execution:

```php
// Run every hour
$this->dispatcher->schedule('cleanup_temp_files', 3600);

// With payload and custom group
$this->dispatcher->schedule('sync_products', 1800, ['source' => 'api'], 'sync');
```

The recurring job is automatically rescheduled after each successful run. If the job fails and exhausts its retries, rescheduling stops.

## Use queue groups

Groups allow you to organize jobs into separate processing lanes. Jobs within the same group are processed sequentially:

```php
class ProcessImageJob implements JobInterface
{
    public function getGroup(): string
    {
        return 'media';
    }

    // ...
}

class SendNotificationJob implements JobInterface
{
    public function getGroup(): string
    {
        return 'notifications';
    }

    // ...
}
```

Each group is processed independently during every cron tick. The `default` group is always processed.

## Configure retry behavior

The `getMaxRetries()` method controls how many times a failed job is retried:

```php
public function getMaxRetries(): int
{
    return 5; // Try up to 5 times before giving up
}
```

When a job throws an exception:
1. The job is marked as `failed` with the error message
2. The attempt counter is incremented
3. If attempts < maxRetries, the job is released back to `pending`
4. If attempts >= maxRetries, the job stays in `failed` status

## Handle errors in jobs

Throw any exception to signal failure. The error message is stored for debugging:

```php
public function handle(array $payload): void
{
    $response = wp_remote_get($payload['url']);

    if (is_wp_error($response)) {
        throw new \RuntimeException('API call failed: ' . $response->get_error_message());
    }

    // Process response...
}
```

## Activate the queue system

Register the `QueueExtension` in your kernel:

```php
protected function getExtensions(): array
{
    return [
        // ... other extensions ...
        new \BackTo\Framework\Queue\QueueExtension(),
    ];
}
```

On plugin activation, the `RegisterQueue` orchestrator creates the `wp_backto_queue_jobs` database table and schedules the cron events. On deactivation, the cron events are cleared.

## Automatic maintenance

The queue system automatically handles:

| Task | Schedule | Description |
|------|----------|-------------|
| Process jobs | Every minute | Claims and executes pending jobs from all groups |
| Rescue stuck jobs | Hourly | Resets jobs stuck in `running` for more than 5 minutes |
| Cleanup completed | Daily | Deletes completed jobs older than 24 hours |
