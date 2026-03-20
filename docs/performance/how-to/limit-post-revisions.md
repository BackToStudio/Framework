# Limit post revisions

Configure the maximum number of revisions per post via `PerformanceConfigurator`:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance->revisionsLimit(3); // keep at most 3 revisions per post
};
```

The default is 5. This applies the `wp_revisions_to_keep` filter.
