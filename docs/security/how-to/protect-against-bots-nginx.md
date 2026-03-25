# Protect against bots with Nginx

*How-to — Task-oriented*

This guide covers setting up full bot protection on an Nginx server, from infrastructure to web server to application.

## Prerequisites

- Root/sudo access to the Nginx server
- A WordPress site using the BackTo Framework with the Security Bundle enabled
- (Recommended) A Cloudflare account or equivalent CDN

## Step 1: Enable CDN-level protection

The most effective method. Traffic is blocked before reaching your server.

1. Put your domain behind Cloudflare (proxy enabled, orange cloud icon).
2. Enable **Bot Fight Mode** in Security > Bots.
3. Create a rate limiting rule in Security > WAF > Rate limiting rules:
   - If requests from the same IP exceed **60 per minute** → **Block**.
4. Create firewall rules to block known scraping bots:
   - Condition: `User-Agent contains "SemrushBot" OR "AhrefsBot" OR "DotBot"`
   - Action: **Block**
5. Set up a Page Rule to cache public pages:
   - URL: `example.com/*`
   - Cache Level: Cache Everything, Edge Cache TTL: 2 hours

## Step 2: Generate the Nginx configuration via the Security Bundle

The Security Bundle generates Nginx configuration from your `config/security.php` parameters:

```php
<?php
// config/security.php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        ->botBlockedUserAgents([
            'SemrushBot', 'AhrefsBot', 'DotBot', 'MJ12bot',
            'BLEXBot', 'PetalBot', 'DataForSeoBot', 'GPTBot', 'CCBot',
        ])
        ->botBlockedIps(['192.0.2.0/24'])
        ->botSensitiveEndpoints(['wp-login.php', 'xmlrpc.php', 'wp-cron.php'])
        ->botGlobalRateLimit(10, 20)
        ->botSensitiveRateLimit(2, 3)
        ->botMaxConnectionsPerIp(20)
        ->botBlockEmptyUserAgent(true);
};
```

Generate the configuration file:

```bash
wp backto:generate-server-config --server=nginx --output=file --dir=/etc/nginx/conf.d
```

This creates `/etc/nginx/conf.d/backto-bot-protection.conf`.

### Include in the Nginx configuration

The generated file contains two sections. The first (`limit_req_zone` directives) must go in the `http {}` block. The second (`location`, `if`, `deny` directives) goes in the `server {}` block:

```nginx
# /etc/nginx/nginx.conf — http {} block
http {
    include /etc/nginx/conf.d/backto-bot-protection.conf;
    # ... rest of config
}
```

Or, if you prefer to separate them:

```nginx
# /etc/nginx/nginx.conf — http {} block
http {
    # Rate limiting zones
    limit_req_zone $binary_remote_addr zone=backto_global:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=backto_sensitive:10m rate=2r/s;
    limit_conn_zone $binary_remote_addr zone=backto_conn:10m;

    # ...
}
```

```nginx
# /etc/nginx/sites-available/example.com — server {} block
server {
    # Block known bot User-Agents
    if ($http_user_agent ~* (SemrushBot|AhrefsBot|DotBot|MJ12bot|BLEXBot|PetalBot|DataForSeoBot|GPTBot|CCBot)) {
        return 403;
    }

    if ($http_user_agent = "") {
        return 403;
    }

    location / {
        limit_req zone=backto_global burst=20 nodelay;
        limit_conn backto_conn 20;
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ ^/(wp-login\.php|xmlrpc\.php|wp-cron\.php) {
        limit_req zone=backto_sensitive burst=3 nodelay;
        limit_conn backto_conn 20;
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }
}
```

Validate and reload:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### Manual configuration (without the generator)

If you do not want to use the generator, copy the configuration above directly into your Nginx files.

## Step 3: Install fail2ban to ban repeat offenders

Create a filter that detects blocked IPs in Nginx logs:

```bash
sudo tee /etc/fail2ban/filter.d/wordpress-bot.conf > /dev/null << 'EOF'
[Definition]
failregex = ^<HOST> .* "(GET|POST|HEAD) .*(wp-login|xmlrpc|wp-cron|wp-json).*" (403|429) .*$
ignoreregex =
EOF
```

Add the jail:

```bash
sudo tee -a /etc/fail2ban/jail.local > /dev/null << 'EOF'
[wordpress-bot]
enabled  = true
port     = http,https
filter   = wordpress-bot
logpath  = /var/log/nginx/access.log
maxretry = 30
findtime = 60
bantime  = 3600
action   = iptables-multiport[name=wordpress-bot, port="http,https", protocol=tcp]
EOF
```

```bash
sudo systemctl restart fail2ban
```

Any IP generating more than 30 blocked requests within 1 minute is banned for 1 hour at the firewall level (iptables).

## Step 4: Configure the application layer

See [Configure application bot protection](./configure-application-bot-protection.md).

## Step 5: Tune PHP-FPM

```ini
; /etc/php/8.2/fpm/pool.d/www.conf
pm = ondemand
pm.max_children = 50
pm.process_idle_timeout = 10s
pm.max_requests = 500
request_terminate_timeout = 30s
```

```bash
sudo systemctl reload php8.2-fpm
```

## Verification

```bash
# Check for 403/429 in Nginx logs
grep -c " 403 " /var/log/nginx/access.log
grep -c " 429 " /var/log/nginx/access.log

# IPs banned by fail2ban
sudo fail2ban-client status wordpress-bot

# Active PHP processes
ps aux | grep php-fpm | wc -l

# Test rate limiting (should return 429 after the limit)
for i in $(seq 1 35); do
    curl -s -o /dev/null -w "%{http_code}\n" https://example.com/wp-json/wp/v2/posts
done
```

## See also

- [Protect against bots with Apache](./protect-against-bots-apache.md)
- [Configure application bot protection](./configure-application-bot-protection.md)
- [Generate server bot protection config](./generate-server-bot-protection-config.md)
- [Bot protection strategy (explanation)](../explanation/bot-protection-strategy.md)
