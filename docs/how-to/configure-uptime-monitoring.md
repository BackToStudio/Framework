# Configure uptime monitoring

The framework provides an internal REST API health endpoint at `GET /wp-json/backto/v1/ops/metrics` and a CLI command `wp backto:health`. However, **uptime monitoring requires an external service** that checks your site from outside your infrastructure.

## Why external monitoring?

A server cannot reliably monitor itself. If the server is down, the monitoring code is down too. External services ping your site from multiple geographic locations and alert you when it becomes unreachable.

## Recommended services

| Service | Free tier | Interval | Alerts |
|---------|-----------|----------|--------|
| [UptimeRobot](https://uptimerobot.com) | 50 monitors, 5 min | 5 min (free) / 1 min (paid) | Email, Slack, webhook |
| [Better Uptime](https://betteruptime.com) | 10 monitors | 3 min | Email, Slack, SMS |
| [Uptime Kuma](https://github.com/louislam/uptime-kuma) | Self-hosted (free) | 1 min | Email, Telegram, Slack |
| [Hetrix Tools](https://hetrixtools.com) | 15 monitors | 1 min | Email, Slack, webhook |

## Setup with UptimeRobot (recommended)

### 1. Create an account

Sign up at [uptimerobot.com](https://uptimerobot.com). The free tier covers most WordPress projects.

### 2. Add an HTTP(S) monitor

| Setting | Value |
|---------|-------|
| Monitor Type | HTTP(s) |
| Friendly Name | `mysite.com` |
| URL | `https://mysite.com` |
| Monitoring Interval | 5 minutes (free) |

### 3. Add a keyword monitor (recommended)

A keyword monitor verifies that the page actually renders, not just that the server returns 200:

| Setting | Value |
|---------|-------|
| Monitor Type | Keyword |
| URL | `https://mysite.com` |
| Keyword | A word present on every page (e.g. your site name) |
| Alert When | Keyword not exists |

### 4. Add a health endpoint monitor

Use the framework's built-in health REST API for deeper checks:

| Setting | Value |
|---------|-------|
| Monitor Type | HTTP(s) |
| URL | `https://mysite.com/wp-json/backto/v1/ops/metrics` |
| Monitoring Interval | 5 minutes |

This endpoint returns the status of all registered health checks (container, cache, database, SMTP, queue, security).

### 5. Configure alerts

Set up at least two alert channels:

- **Email** — immediate notification
- **Slack/Teams webhook** — team visibility

## Setup with Uptime Kuma (self-hosted)

If you prefer self-hosted monitoring:

```bash
docker run -d --restart=always -p 3001:3001 \
  -v uptime-kuma:/app/data \
  --name uptime-kuma \
  louislam/uptime-kuma:1
```

Then add monitors in the web UI at `http://your-server:3001`.

## Best practices

1. **Monitor from outside your hosting provider** — If your host goes down, a monitor on the same infrastructure won't help
2. **Set up at least 2 check types** — HTTP status + keyword or health endpoint
3. **Configure multiple alert channels** — Email alone can be missed; add Slack or SMS
4. **Monitor the health endpoint** — Not just the homepage; the health endpoint checks database, cache, SMTP
5. **Set reasonable intervals** — 5 minutes is sufficient for most sites; 1 minute for critical/e-commerce
6. **Create a status page** — UptimeRobot and Uptime Kuma both offer public status pages for client transparency

## Integration with BackTo Framework

The framework's health check system complements external uptime monitoring:

| Layer | Tool | What it checks |
|-------|------|----------------|
| **External** | UptimeRobot | Is the site reachable from the internet? |
| **Application** | `GET /ops/metrics` | Are internal services (DB, cache, SMTP) healthy? |
| **CLI** | `wp backto:health` | On-demand diagnostic from the server |
| **Scheduled** | `ScheduleHealthChecks` | Hourly automated checks with alerts |

Together, these layers provide full coverage: external availability + internal service health.
