# Configuration

### `SecurityConfigurator`

Fluent API used in `config/security.php`.

| Method | DI Parameter | Default |
|---|---|---|
| `headersEnabled(bool)` | `security.headers_enabled` | `true` |
| `xmlrpcDisabled(bool)` | `security.xmlrpc_disabled` | `true` |
| `hideVersion(bool)` | `security.hide_version` | `true` |
| `cspReportOnly(bool)` | `security.csp_report_only` | `false` |
| `passwordMinLength(int)` | `security.password_min_length` | `12` |
| `maxConcurrentSessions(int)` | `security.max_concurrent_sessions` | `1` |
| `restApiRequireAuth(bool)` | `security.rest_api_require_auth` | `true` |
| `disableFileEditor(bool)` | `security.disable_file_editor` | `true` |
| `twoFactorEnabled(bool)` | `security.two_factor_enabled` | `false` |
| `twoFactorIssuer(string)` | `security.two_factor_issuer` | `'WordPress'` |
