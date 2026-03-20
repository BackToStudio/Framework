# Send custom headers

```php
$response = $this->httpClient->get('https://api.example.com/data', [
    'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
        'Accept'        => 'application/json',
        'X-Request-ID'  => wp_generate_uuid4(),
    ],
]);
```
