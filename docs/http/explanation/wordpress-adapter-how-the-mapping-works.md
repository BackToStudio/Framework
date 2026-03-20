# WordPress adapter: how the mapping works

```
HttpClientInterface::get($url, $options)
        │
        ▼
WordPressHttpClient::request('GET', $url, $options)
        │
        ├── buildArgs(): maps $options to WordPress $args format
        │   ├── timeout → timeout
        │   ├── headers → headers
        │   ├── body → body
        │   ├── blocking → blocking
        │   ├── sslverify → sslverify
        │   └── cookies → cookies
        │
        ├── wp_remote_request($url, $args)
        │
        └── toResponse(): converts WP response to PSR-7
            ├── WP_Error → Response(0, [], $errorMessage)
            └── array → Response($statusCode, $headers, $body)
```

The `sendRequest(RequestInterface)` path works differently: it extracts method, URI, headers, and body from the PSR-7 request object and passes them to `wp_remote_request()`.
