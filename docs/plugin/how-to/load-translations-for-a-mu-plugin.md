# Load translations for a mu-plugin

Use `LoadMuPluginTextDomain` instead of `LoadPluginTextDomain`. It calls `load_muplugin_textdomain()`, which looks for translations in `mu-plugins/my-plugin/languages/`.

Ensure your service configuration loads the `I18n` namespace:

```php
<?php

$services->load('MyPlugin\\I18n\\', 'I18n/*');
```
