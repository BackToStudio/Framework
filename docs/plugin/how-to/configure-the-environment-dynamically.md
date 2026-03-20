# Configure the environment dynamically

Pass WordPress environment functions to the kernel constructor:

```php
<?php

use BackTo\Framework\Bundle\Plugin\PluginKernel;

$kernel = new PluginKernel(
    environment: wp_get_environment_type(),
    debug: WP_DEBUG,
);
```

Setting `debug: true` disables the DI container cache, useful during development.
