# Error handling strategy

Network errors (DNS failure, timeout, SSL issues) in WordPress return a `WP_Error` object. The adapter converts these to a `Response` with:
- Status code: `0` (distinguishes network errors from HTTP errors like 404/500)
- Body: the error message from `WP_Error`

This avoids throwing exceptions for network issues, keeping the API consistent: every call returns a `ResponseInterface`. Consumers check `$response->getStatusCode() === 0` for network failures.
