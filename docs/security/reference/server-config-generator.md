# ServerConfigGenerator

`BackTo\Framework\Bundle\Security\Network\ServerConfigGenerator`

Generates Nginx and Apache configuration files to block bot traffic at the web-server level, before PHP is invoked.

## DI registration

The service is registered automatically by the Security Bundle. Its setters are called by the `ConfigureServerConfigGeneratorPass` compiler pass from the `security.bot_protection.*` container parameters.

## Public methods

### Configuration

| Method | Description |
|---|---|
| `setBlockedUserAgents(string[])` | Replace the list of blocked User-Agents |
| `addBlockedUserAgents(string[])` | Add User-Agents to the existing list |
| `setBlockedIps(string[])` | Set IP/CIDR ranges to block |
| `setSensitiveEndpoints(string[])` | Set URI patterns with stricter rate limiting |
| `setGlobalRateLimit(int $rps, int $burst)` | Global limit in requests/second + burst |
| `setSensitiveRateLimit(int $rps, int $burst)` | Stricter limit for sensitive endpoints |
| `setMaxConnectionsPerIp(int)` | Max simultaneous connections per IP |
| `setBlockEmptyUserAgent(bool)` | Block requests with no User-Agent header |

All setters return `$this` (fluent interface).

### Generation

| Method | Description |
|---|---|
| `generateNginx(): string` | Returns the complete Nginx configuration |
| `generateApache(): string` | Returns the complete Apache configuration |
| `writeNginx(string $path): bool` | Writes the Nginx configuration to a file |
| `writeApache(string $path): bool` | Writes the Apache configuration to a file |
| `getConfiguration(): array` | Returns the current configuration |

### `getConfiguration()` — Return format

```php
[
    'blocked_user_agents'    => string[],
    'blocked_ips'            => string[],
    'sensitive_endpoints'    => string[],
    'global_rate_limit'      => int,
    'global_burst'           => int,
    'sensitive_rate_limit'   => int,
    'sensitive_burst'        => int,
    'max_connections_per_ip' => int,
    'block_empty_user_agent' => bool,
]
```

## Generated output — Nginx

The generated file contains two sections:

**Section 1 — `http {}` block** (rate limiting zones):

```nginx
limit_req_zone $binary_remote_addr zone=backto_global:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=backto_sensitive:10m rate=2r/s;
limit_conn_zone $binary_remote_addr zone=backto_conn:10m;
```

**Section 2 — `server {}` block**:

- User-Agent blocking (`if` + `return 403`)
- Empty User-Agent blocking
- IP `deny` directives
- `location /` with `limit_req` and `limit_conn`
- `location ~` for sensitive endpoints

## Generated output — Apache

The generated file contains:

- `mod_rewrite`: User-Agent blocking (`RewriteCond` + `RewriteRule`)
- `mod_authz_core`: IP blocking (`Require not ip`)
- `mod_evasive24`: rate limiting (`DOSPageCount`, `DOSSiteCount`, etc.)

## Compiler pass

`ConfigureServerConfigGeneratorPass` reads container parameters and calls the corresponding setters:

| DI Parameter | Method called |
|---|---|
| `security.bot_protection.blocked_user_agents` | `setBlockedUserAgents()` |
| `security.bot_protection.blocked_ips` | `setBlockedIps()` |
| `security.bot_protection.sensitive_endpoints` | `setSensitiveEndpoints()` |
| `security.bot_protection.global_rate_limit` + `global_burst` | `setGlobalRateLimit()` |
| `security.bot_protection.sensitive_rate_limit` + `sensitive_burst` | `setSensitiveRateLimit()` |
| `security.bot_protection.max_connections_per_ip` | `setMaxConnectionsPerIp()` |
| `security.bot_protection.block_empty_user_agent` | `setBlockEmptyUserAgent()` |

## WP-CLI command

```
wp backto:generate-server-config [--server=<nginx|apache|both>] [--output=<stdout|file>] [--dir=<path>] [--blocked-ips=<ips>] [--extra-bots=<bots>]
```

| Option | Description | Default |
|---|---|---|
| `--server` | Web server type | `nginx` |
| `--output` | Output mode | `stdout` |
| `--dir` | Output directory (`file` mode) | `.` |
| `--blocked-ips` | Additional IPs/CIDRs (comma-separated) | — |
| `--extra-bots` | Additional User-Agent names (comma-separated) | — |

Generated files:
- Nginx: `backto-bot-protection.conf`
- Apache: `.htaccess-bot-protection`
