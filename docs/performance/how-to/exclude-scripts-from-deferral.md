# Exclude scripts from deferral

By default, all frontend scripts receive the `defer` attribute except `jquery-core` and `jquery-migrate`. To exclude additional scripts:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance->deferExclude([
        'jquery-core',
        'jquery-migrate',
        'my-critical-script',
    ]);
};
```
