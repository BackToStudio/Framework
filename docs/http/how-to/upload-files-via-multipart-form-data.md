# Upload files via multipart form data

WordPress HTTP API supports file uploads using `CURLFile`:

```php
<?php

$boundary = wp_generate_password(24, false);

$body = "--{$boundary}\r\n";
$body .= "Content-Disposition: form-data; name=\"title\"\r\n\r\n";
$body .= "My Document\r\n";
$body .= "--{$boundary}\r\n";
$body .= "Content-Disposition: form-data; name=\"file\"; filename=\"report.pdf\"\r\n";
$body .= "Content-Type: application/pdf\r\n\r\n";
$body .= file_get_contents('/path/to/report.pdf') . "\r\n";
$body .= "--{$boundary}--\r\n";

$response = $this->httpClient->post('https://api.example.com/upload', [
    'headers' => [
        'Content-Type' => "multipart/form-data; boundary={$boundary}",
    ],
    'body' => $body,
]);
```
