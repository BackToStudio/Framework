# Post form data

```php
$response = $this->httpClient->post('https://api.example.com/form', [
    'body' => [
        'name'  => 'John',
        'email' => 'john@example.com',
    ],
]);
```

When `body` is an array, WordPress sends it as `application/x-www-form-urlencoded`.
