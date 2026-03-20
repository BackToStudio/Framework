# Configure auto-update policy

```php
<?php

use BackTo\Framework\Bundle\Security\AutoUpdatePolicy;

$autoUpdate = $container->get(AutoUpdatePolicy::class);

$autoUpdate->setMajorCore(false);
$autoUpdate->setMinorCore(true);
$autoUpdate->setPlugins(false);
$autoUpdate->setThemes(false);
$autoUpdate->setTranslations(true);

$autoUpdate->setAllowedPlugins(['akismet/akismet.php']);
$autoUpdate->setAllowedThemes(['twentytwentyfour']);
```
