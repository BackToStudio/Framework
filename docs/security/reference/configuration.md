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

### Bot protection

| Method | DI Parameter | Default |
|---|---|---|
| `botBlockedUserAgents(string[])` | `security.bot_protection.blocked_user_agents` | `['SemrushBot', 'AhrefsBot', 'DotBot', 'MJ12bot', 'BLEXBot', 'PetalBot', 'DataForSeoBot', 'GPTBot', 'CCBot']` |
| `botBlockedIps(string[])` | `security.bot_protection.blocked_ips` | `[]` |
| `botSensitiveEndpoints(string[])` | `security.bot_protection.sensitive_endpoints` | `['wp-login.php', 'xmlrpc.php', 'wp-cron.php']` |
| `botGlobalRateLimit(int $rps, int $burst)` | `security.bot_protection.global_rate_limit`, `security.bot_protection.global_burst` | `10`, `20` |
| `botSensitiveRateLimit(int $rps, int $burst)` | `security.bot_protection.sensitive_rate_limit`, `security.bot_protection.sensitive_burst` | `2`, `3` |
| `botMaxConnectionsPerIp(int)` | `security.bot_protection.max_connections_per_ip` | `20` |
| `botBlockEmptyUserAgent(bool)` | `security.bot_protection.block_empty_user_agent` | `true` |
