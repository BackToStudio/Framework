# Disable individual .htaccess optimizations

Each `.htaccess` directive group can be toggled independently:

```php
$performance
    ->htaccessGzip(false)          // disable gzip compression
    ->htaccessBrowserCache(true)   // keep browser caching
    ->htaccessRemoveEtags(true)    // keep ETag removal
    ->htaccessKeepAlive(false);    // disable Keep-Alive
```
