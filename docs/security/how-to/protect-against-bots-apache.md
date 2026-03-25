# Protect against bots with Apache

*How-to — Task-oriented*

This guide covers setting up full bot protection on an Apache server, from infrastructure to web server to application.

## Prerequisites

- Root/sudo access to the Apache server
- Modules enabled: `mod_rewrite`, `mod_evasive` (optional), `mod_authz_core`
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

## Step 2: Generate the Apache configuration via the Security Bundle

The Security Bundle generates Apache configuration from your `config/security.php` parameters:

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
wp backto:generate-server-config --server=apache --output=file --dir=/var/www/html
```

This creates `/var/www/html/.htaccess-bot-protection`.

### Include in the Apache configuration

#### Option A: Merge into .htaccess

```bash
cat /var/www/html/.htaccess-bot-protection >> /var/www/html/.htaccess
```

#### Option B: Include in VirtualHost

```apache
# /etc/apache2/sites-available/example.com.conf
<VirtualHost *:443>
    ServerName example.com
    DocumentRoot /var/www/html

    Include /var/www/html/.htaccess-bot-protection

    # ... rest of config
</VirtualHost>
```

Reload Apache:

```bash
sudo apachectl configtest && sudo systemctl reload apache2
```

### Manual configuration (without the generator)

If you prefer to configure manually, add the following to `.htaccess` or the VirtualHost:

#### Block bot User-Agents

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Block known scraping bots
    RewriteCond %{HTTP_USER_AGENT} (SemrushBot|AhrefsBot|DotBot|MJ12bot|BLEXBot|PetalBot|DataForSeoBot|GPTBot|CCBot) [NC]
    RewriteRule .* - [F,L]

    # Block requests with empty User-Agent
    RewriteCond %{HTTP_USER_AGENT} ^$ [NC]
    RewriteRule .* - [F,L]
</IfModule>
```

#### Block specific IPs

```apache
# Apache 2.4+ (mod_authz_core)
<RequireAll>
    Require all granted
    Require not ip 192.0.2.0/24
    Require not ip 198.51.100.50
</RequireAll>
```

For Apache 2.2:

```apache
<IfModule !mod_authz_core.c>
    Order allow,deny
    Allow from all
    Deny from 192.0.2.0/24
    Deny from 198.51.100.50
</IfModule>
```

#### Rate limiting with mod_evasive

```bash
sudo a2enmod evasive
```

```apache
<IfModule mod_evasive24.c>
    DOSHashTableSize    3097
    DOSPageCount        5         # max 5 requests to the same page/second
    DOSSiteCount        50        # max 50 requests to the site/second
    DOSPageInterval     1
    DOSSiteInterval     1
    DOSBlockingPeriod   60        # ban for 60 seconds
    DOSEmailNotify      admin@example.com
    DOSLogDir           "/var/log/mod_evasive"
</IfModule>
```

```bash
sudo mkdir -p /var/log/mod_evasive
sudo chown www-data:www-data /var/log/mod_evasive
```

#### Protect sensitive endpoints

```apache
# Block xmlrpc.php entirely (if not used)
<Files "xmlrpc.php">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</Files>
```

## Step 3: Install fail2ban to ban repeat offenders

Create a filter that detects blocked IPs in Apache logs:

```bash
sudo tee /etc/fail2ban/filter.d/wordpress-bot.conf > /dev/null << 'EOF'
[Definition]
failregex = ^<HOST> .* "(GET|POST|HEAD) .*(wp-login|xmlrpc|wp-cron|wp-json).*" (403|429) .*$
ignoreregex =
EOF
```

Add the jail (adjust the log path for your setup):

```bash
sudo tee -a /etc/fail2ban/jail.local > /dev/null << 'EOF'
[wordpress-bot]
enabled  = true
port     = http,https
filter   = wordpress-bot
logpath  = /var/log/apache2/access.log
maxretry = 30
findtime = 60
bantime  = 3600
action   = iptables-multiport[name=wordpress-bot, port="http,https", protocol=tcp]
EOF
```

```bash
sudo systemctl restart fail2ban
```

Any IP generating more than 30 blocked requests within 1 minute is banned for 1 hour at the firewall level.

## Step 4: Configure the application layer

See [Configure application bot protection](./configure-application-bot-protection.md).

## Step 5: Tune PHP

### PHP-FPM (mod_proxy_fcgi)

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

### mod_php (prefork MPM)

```apache
# /etc/apache2/mods-available/mpm_prefork.conf
<IfModule mpm_prefork_module>
    StartServers          5
    MinSpareServers       5
    MaxSpareServers      10
    MaxRequestWorkers    50
    MaxConnectionsPerChild 500
</IfModule>
```

```bash
sudo systemctl reload apache2
```

## Recommended Apache modules

| Module | Purpose | Activation |
|---|---|---|
| `mod_rewrite` | User-Agent blocking, redirects | `sudo a2enmod rewrite` |
| `mod_evasive` | Rate limiting / anti-DDoS | `sudo apt install libapache2-mod-evasive` |
| `mod_security2` | WAF (Web Application Firewall) | `sudo apt install libapache2-mod-security2` |
| `mod_authz_core` | IP access control (Apache 2.4+) | Enabled by default |
| `mod_headers` | Security headers | `sudo a2enmod headers` |

## Verification

```bash
# Check for 403 in Apache logs
grep -c " 403 " /var/log/apache2/access.log

# IPs banned by fail2ban
sudo fail2ban-client status wordpress-bot

# Active PHP processes
ps aux | grep php | wc -l

# Test User-Agent blocking (should return 403)
curl -s -o /dev/null -w "%{http_code}\n" -A "SemrushBot" https://example.com/
```

## See also

- [Protect against bots with Nginx](./protect-against-bots-nginx.md)
- [Configure application bot protection](./configure-application-bot-protection.md)
- [Generate server bot protection config](./generate-server-bot-protection-config.md)
- [Bot protection strategy (explanation)](../explanation/bot-protection-strategy.md)
