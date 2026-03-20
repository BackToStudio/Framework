# Defense in depth

The bundle applies **defense in depth**: each security layer operates independently. If one layer is bypassed, the others remain active. The layers are ordered by the request lifecycle:

```
Incoming request
  |
  v
[1. HTTP headers]         HttpHeadersHardening, CSP, CookieHardening
  |
  v
[2. Access control]       IPAccessControl, RestApiSecurity, DisableXmlRpc
  |
  v
[3. Rate limiting]        RestApiRateLimiter
  |
  v
[4. Authentication]       LoginHardening (throttle) -> 2FA -> SessionManager
  |
  v
[5. Authorization]        CapabilityHardening, DisableFileEditor
  |
  v
[6. Input validation]     DatabaseHardening, UploadSecurity, CommentSpamProtection
  |
  v
[7. Monitoring]           SecurityAuditLogger, LoginAnomalyDetector, FileIntegrityMonitor
  |
  v
[8. Notification]         SecurityNotifier
```

Even if an attacker bypasses rate limiting (layer 3), they still face login throttling and 2FA (layer 4), capability restrictions (layer 5), and every attempt is logged by layer 7.
