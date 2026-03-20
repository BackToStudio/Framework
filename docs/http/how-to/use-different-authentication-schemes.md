# Use different authentication schemes

### Basic Auth

```php
$response = $this->httpClient->get('https://api.example.com/data', [
    'headers' => [
        'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
    ],
]);
```

### API Key in header

```php
$response = $this->httpClient->get('https://api.example.com/data', [
    'headers' => [
        'X-API-Key' => $apiKey,
    ],
]);
```

### API Key as query parameter

```php
$response = $this->httpClient->get('https://api.example.com/data?api_key=' . urlencode($apiKey));
```
