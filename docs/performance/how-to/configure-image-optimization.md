# Configure image optimization

### Control how many images skip lazy-loading

The first image (likely the LCP element) is excluded from lazy-loading and receives `fetchpriority="high"` by default. To adjust:

```php
$performance->lazyLoadSkipFirst(2); // first 2 images are not lazy-loaded
```

### Disable decoding="async"

```php
$performance->addDecodingAsync(false);
```

### Disable fetchpriority="high"

```php
$performance->addFetchpriority(false);
```
