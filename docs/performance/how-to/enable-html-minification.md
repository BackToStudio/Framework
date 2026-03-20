# Enable HTML minification

HTML minification is disabled by default. Enable it via `PerformanceConfigurator`:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance->minifyHtml(true);
};
```

This removes HTML comments, collapses whitespace between block-level tags, minifies inline CSS and JavaScript, and strips redundant `type` attributes. Content inside `<pre>`, `<code>`, and `<textarea>` is preserved.
