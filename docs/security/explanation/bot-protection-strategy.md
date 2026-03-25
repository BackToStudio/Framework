# Bot protection strategy

*Explanation — Understanding-oriented*

## The problem

Scraping bots, aggressive crawlers, and automated attack tools are a direct threat to the availability of a WordPress site. Every bot request spawns a PHP process, consumes memory, and hits the database. When hundreds of bots hit simultaneously, the server saturates: PHP processes pile up, workers are exhausted, and the site becomes unreachable for real users.

## The principle: block as early as possible

The defense strategy follows a simple principle: **each layer intercepts traffic before it reaches the next one**. The earlier you block, the less the server works.

```
Incoming request
  |
  v
[Layer 1 — Network / CDN]         Cloudflare, AWS WAF, etc.
  |                                Blocks before reaching the server
  v
[Layer 2 — Web server]            Nginx / Apache
  |                                Blocks without launching PHP
  v
[Layer 3 — System]                fail2ban, ModSecurity
  |                                Bans repeat offenders at the firewall
  v
[Layer 4 — Application]           BackTo Framework Security Bundle
                                   Rate limiting, IP control, honeypot
```

This model builds on the [defense in depth](./defense-in-depth.md) principle already applied by the Security Bundle.

## Layer 1 — Network / CDN

The most effective layer. Malicious traffic is blocked **before reaching your server**. No PHP process is launched, no server bandwidth is consumed.

**Cloudflare** (recommended) provides:
- **Bot Fight Mode**: identification via TLS fingerprint (JA3/JA4), IP reputation, behavioral analysis.
- **WAF Managed Rules**: pre-configured rules (SQLi, XSS, path traversal).
- **Rate Limiting**: per-IP limiting before the server.
- **Firewall Rules**: blocking by country, ASN, User-Agent, or URI.
- **Caching**: pages served directly from the CDN without hitting the server.

**Alternatives**: AWS CloudFront + WAF, Sucuri Firewall, Fastly.

**Why this is the priority**: a bot blocked at the CDN consumes zero server resources. It is the best cost/effectiveness ratio.

## Layer 2 — Web server

If a bot passes the CDN (or if you don't have one), the web server can block it **before launching PHP**. This is critical because spawning PHP processes is what saturates the server.

### Nginx

Nginx handles rate limiting in memory via shared zones (`limit_req_zone`). Each zone stores a per-IP counter. Excess requests are rejected with HTTP 429 without ever invoking the FastCGI/PHP handler.

Available mechanisms:
- **`limit_req_zone`**: per-IP throughput limiting (requests/second with burst).
- **`limit_conn_zone`**: per-IP simultaneous connection limiting.
- **`if ($http_user_agent)`**: User-Agent blocking via regex.
- **`deny`**: IP/CIDR blocking.

### Apache

Apache provides several modules for the same purpose:
- **`mod_rewrite`**: User-Agent blocking via `RewriteCond` (built-in, always available).
- **`mod_evasive`**: lightweight rate limiting with burst detection (add-on module).
- **`mod_security2`**: full WAF with OWASP rulesets (add-on module).
- **`mod_authz_core`**: IP access control via `Require not ip` (built-in, Apache 2.4+).

### Configuration generator

The Security Bundle's `ServerConfigGenerator` generates Nginx and Apache configurations from the `config/security.php` parameters. This provides two benefits:
1. **Single source of truth**: the same parameters (User-Agents, IPs, rate limits) feed both the server layer and the application layer.
2. **Reproducibility**: the server configuration is regenerated on every parameter change via `wp backto:generate-server-config`.

## Layer 3 — System

`fail2ban` monitors web server logs and bans repeat offender IPs at the firewall level (iptables/nftables). The IP is blocked before the web server even processes the request.

The mechanism:
1. fail2ban reads access logs continuously.
2. A regex filter identifies blocked requests (403, 429) to sensitive endpoints.
3. When an IP exceeds the threshold (e.g., 30 requests in 1 minute), an iptables rule blocks it.
4. After the ban duration expires (e.g., 1 hour), the rule is removed.

This complements web server rate limiting: Nginx/Apache blocks each individual request, fail2ban bans repeat offenders.

## Layer 4 — Application

If a bot reaches PHP despite the previous layers, the Security Bundle takes over with:
- **`RestApiRateLimiter`**: per-IP rate limiting on REST endpoints (HTTP 429).
- **`IPAccessControl`**: application-level whitelist/blacklist with CIDR support.
- **`CommentSpamProtection`**: honeypot, referer validation, content analysis.
- **`RestApiSecurity`**: authentication required for the REST API.

This layer spawns a PHP process, but cuts it short quickly (429/403 without heavy processing).

## Impact on PHP processes

| Layer | PHP process spawned? | Server cost |
|---|---|---|
| CDN (Cloudflare) | No | None |
| Web server (Nginx/Apache) | No | Negligible |
| System (fail2ban/iptables) | No | None |
| Application (Security Bundle) | Yes, but cut short | Low |

The goal is to **minimize the number of requests that reach PHP**. Each layer acts as a progressive filter.

## PHP-FPM tuning

In addition to the defense layers, tune PHP-FPM to withstand traffic spikes:

```ini
; /etc/php/8.2/fpm/pool.d/www.conf
pm = ondemand
pm.max_children = 50
pm.process_idle_timeout = 10s
pm.max_requests = 500
request_terminate_timeout = 30s
```

- **`pm = ondemand`**: only spawns workers when there are requests.
- **`pm.max_children`**: caps the number of simultaneous PHP processes. Adjust based on your RAM (~30-50 MB per worker).
- **`pm.max_requests = 500`**: recycles workers to prevent memory leaks.
- **`request_terminate_timeout = 30s`**: kills processes that take too long.

## Robots.txt

The `robots.txt` file does not block malicious bots (they ignore it), but it reduces load from legitimate bots that respect the convention. It is a passive signal — useful but insufficient on its own.

## Recommended deployment order

1. **Cloudflare (or equivalent CDN)** — immediate impact, no server changes.
2. **Nginx/Apache rate limiting** — blocks without PHP, quick setup.
3. **fail2ban** — automatically bans repeat offenders.
4. **Security Bundle (rate limiter + IP control)** — application-level safety net.
5. **PHP-FPM tuning** — resilience during traffic spikes.
6. **Monitoring** — use `SecurityAuditLogger` to detect patterns and adjust rules.

Each layer reinforces the others. None is sufficient alone.

## See also

- [Defense in depth](./defense-in-depth.md)
- [Protect against bots with Nginx](../how-to/protect-against-bots-nginx.md)
- [Protect against bots with Apache](../how-to/protect-against-bots-apache.md)
- [Configure application bot protection](../how-to/configure-application-bot-protection.md)
- [ServerConfigGenerator — Reference](../reference/server-config-generator.md)
