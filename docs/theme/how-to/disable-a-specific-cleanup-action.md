# Disable a specific cleanup action

All cleanup actions are enabled by default. To keep WordPress emojis, for example, exclude `RemoveEmojis` from service autoloading:

```php
<?php

$services->load('BackTo\\Framework\\Bundle\\Theme\\Actions\\', 'Actions/*')
    ->exclude('Actions/RemoveEmojis.php');
```

The same approach works for any cleanup action (`CleanHead`, `RemoveWordPressVersion`, `RemoveSvgFilters`, `RemoveNavigationFallback`).
