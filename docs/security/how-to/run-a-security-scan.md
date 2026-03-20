# Run a security scan

Send a GET request to the scan endpoint (requires `manage_options` capability):

```
GET /wp-json/backto/v1/security/scan
```

The response includes file integrity results (SHA-256 baseline comparison) and malware scan results (PHP files in the uploads directory).
