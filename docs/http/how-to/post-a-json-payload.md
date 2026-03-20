# Post a JSON payload

```php
$response = $this->httpClient->post('https://api.example.com/events', [
    'headers' => ['Content-Type' => 'application/json'],
    'body'    => json_encode([
        'event' => 'page_viewed',
        'url'   => home_url('/'),
    ]),
]);
```
