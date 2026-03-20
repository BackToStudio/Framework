# Customize .htaccess static asset TTL

The default TTL for static assets (CSS, JS, images, fonts) in `.htaccess` is 31,536,000 seconds (1 year):

```php
$performance->htaccessStaticTtl(2592000); // 30 days
```

A one-year TTL with versioned filenames (hash in the filename) is the recommended strategy. HTML files are never browser-cached (`max-age=0`) because the server-side page cache manages them.
