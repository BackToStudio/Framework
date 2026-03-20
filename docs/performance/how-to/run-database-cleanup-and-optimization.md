# Run database cleanup and optimization

Use `DatabaseOptimizerInterface` to clean up stale data:

```php
<?php

use BackTo\Framework\Bundle\Performance\Contracts\DatabaseOptimizerInterface;

$optimizer = $container->get(DatabaseOptimizerInterface::class);

// Run all cleanup tasks
$results = $optimizer->cleanup();
// [
//     'excess_revisions' => 342,
//     'auto_drafts' => 5,
//     'trashed_posts' => 12,
//     'spam_comments' => 89,
//     'trashed_comments' => 7,
//     'expired_transients' => 156,
//     'orphaned_post_meta' => 23,
//     'orphaned_comment_meta' => 4,
// ]

// Optimize database tables
$tablesOptimized = $optimizer->optimizeTables();
```

Cleanup tasks include: excess post revisions (beyond the configured limit), auto-drafts, trashed posts/comments, spam comments, expired transients, and orphaned metadata.
