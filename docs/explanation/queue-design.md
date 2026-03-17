# Queue System Design

## The problem

WordPress executes PHP synchronously — every operation blocks the HTTP response. Sending emails, processing images, calling external APIs, or running bulk operations during a page request degrades user experience and can trigger timeouts.

WordPress provides `wp_schedule_single_event()` and `wp_cron` for deferred execution, but these are limited:

- No retry logic on failure
- No visibility into job status
- No deduplication or grouping
- No structured payload passing
- No cleanup of completed tasks

Plugins like Action Scheduler solve this, but adding a third-party dependency to a framework creates coupling and versioning constraints.

## The solution

The Queue module provides a lightweight, framework-native job queue built on the same Clean Architecture patterns as every other module. It stores jobs in a dedicated database table and processes them via WP-Cron.

## Architecture decisions

### Custom table vs. custom post type

Jobs are stored in a `wp_backto_queue_jobs` custom table rather than using WordPress posts. This decision was made for several reasons:

1. **Atomic claiming**: The worker uses `UPDATE ... WHERE status = 'pending' ORDER BY scheduled_at LIMIT 1` to atomically claim a job. This prevents two concurrent cron workers from processing the same job. This pattern is unreliable with `wp_posts` due to WordPress's abstraction layer.

2. **Performance**: A queue generates high write volume (insert, update status, delete). The `wp_posts` table is already a contention point in WordPress — adding queue throughput would degrade overall performance.

3. **Clean lifecycle**: Completed jobs are garbage collected after 24 hours. Using posts would leave orphaned meta rows and pollute the post count.

4. **Dedicated indexes**: The table has indexes optimized for queue access patterns (`status + group + scheduled_at`, `job_key + status`, `status + claimed_at`).

### WP-Cron vs. real cron

The queue hooks into WP-Cron's `every_minute` schedule. This has a well-known limitation: WP-Cron only fires when someone visits the site. For reliable execution:

- Configure a real server-side cron: `* * * * * cd /path/to/wp && php wp-cron.php --doing_wp_cron >/dev/null 2>&1`
- The framework's `DisablePublicCron` security rule (in the Security module) already recommends and enforces this.

### Groups for sequential processing

Jobs are organized into groups. Within a group, jobs are claimed one at a time in `scheduled_at` order. Different groups are processed independently in the same cron tick.

This allows you to:
- Isolate slow jobs (image processing) from fast jobs (sending emails)
- Ensure ordering within a domain (e.g., process payment before sending receipt)
- Prevent one failing job type from blocking unrelated work

### Retry with fail-then-release

When a job fails:

1. `markFailed()` increments `attempts` and stores the error message
2. The worker re-reads the job from the database to get the updated count
3. If `attempts < maxRetries`, `release()` resets the status to `pending`
4. If retries are exhausted, the job stays in `failed`

This two-step approach (fail → conditionally release) ensures the error is always recorded, even when the job will be retried. It also provides a clean audit trail of how many attempts occurred.

### Recurring job rescheduling

Recurring jobs are not scheduled via WP-Cron recurrence. Instead, the worker enqueues a new job after each successful execution. This approach:

- Prevents job pile-up: if a job takes longer than its interval, the next one is only scheduled after the current one finishes
- Stops rescheduling on failure: if a recurring job permanently fails, it stops rather than retrying forever
- Uses the same queue infrastructure for both one-off and recurring work

### Stuck job rescue

A job claimed by a worker that crashes (fatal error, timeout, OOM kill) will remain in `running` status forever. The hourly rescue cron resets any job that has been in `running` for more than 5 minutes back to `pending`.

This timeout is deliberately conservative — most WordPress operations complete in seconds. Jobs that legitimately run longer should be split into smaller units of work.

## Comparison with Action Scheduler

| Feature | BackTo Queue | Action Scheduler |
|---------|-------------|-----------------|
| Storage | Custom table | Custom table |
| Processing | WP-Cron | WP-Cron + async request |
| Retry | Configurable per job type | Global retry count |
| Groups | Native support | Via group parameter |
| Recurring | Re-enqueue after success | Cron-based recurrence |
| Unique jobs | `dispatchUnique()` deduplication | No built-in dedup |
| DI integration | Auto-discovered via container | Manual hook registration |
| Framework coupling | Built on BackTo patterns | Standalone library |
