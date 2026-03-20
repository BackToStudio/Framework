# Set a request timeout

Pass `timeout` in the options array (seconds):

```php
$response = $this->httpClient->get('https://api.example.com/slow-endpoint', [
    'timeout' => 5,
]);
```

Default timeout is determined by WordPress (typically 5 seconds).
