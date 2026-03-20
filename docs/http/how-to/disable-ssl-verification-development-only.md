# Disable SSL verification (development only)

```php
$response = $this->httpClient->get('https://localhost:8443/api', [
    'sslverify' => false,
]);
```

> **Warning:** Never disable SSL verification in production.
