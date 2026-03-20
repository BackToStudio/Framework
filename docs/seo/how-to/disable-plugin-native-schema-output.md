# Disable plugin-native schema output

By default, the framework disables JSON-LD output from Yoast SEO and SEOPress to prevent duplicates. To keep the plugin's schema:

```php
<?php

// config/seo.php
return [
    'schema' => [
        'disable_plugin_schema' => false,
    ],
];
```
