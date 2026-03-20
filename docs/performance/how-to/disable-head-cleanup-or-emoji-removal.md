# Disable head cleanup or emoji removal

These are enabled by default. To disable:

```php
$performance
    ->cleanHead(false)
    ->disableEmojis(false)
    ->disableEmbeds(false)
    ->disableXmlrpc(false);
```
