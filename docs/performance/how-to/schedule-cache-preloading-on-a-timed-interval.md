# Schedule cache preloading on a timed interval

Beyond post-publish preloading, warm your entire cache on a schedule:

```php
<?php

namespace MyPlugin\Performance;

use BackTo\Framework\Bundle\Performance\PreloadUrlCollector;
use BackTo\Framework\Bundle\Performance\PreloadExecutor;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\CronSchedulerInterface;

final class ScheduledCacheWarmer
{
    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
        private readonly CronSchedulerInterface $cronScheduler,
        private readonly PreloadUrlCollector $urlCollector,
        private readonly PreloadExecutor $executor,
    ) {}

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_init', [$this, 'schedule']);
        $this->hookDispatcher->addAction('my_plugin_warm_cache', [$this, 'warm']);
    }

    public function schedule(): void
    {
        $this->cronScheduler->scheduleRecurring(
            'my_plugin_warm_cache',
            'twicedaily',
            time()
        );
    }

    public function warm(): void
    {
        $urls = $this->urlCollector->getSiteUrls();
        $this->executor->preload($urls);
    }
}
```

The `PreloadUrlCollector` gathers URLs from: home page, blog page, recent posts, pages, category archives, and top tags — limited by the configured batch size.
