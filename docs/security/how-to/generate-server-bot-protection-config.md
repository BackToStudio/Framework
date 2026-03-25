# Generate server bot protection configuration

The `ServerConfigGenerator` produces Nginx and Apache configuration snippets that block bot traffic at the web-server level, before PHP is invoked. This eliminates the overhead of launching PHP processes for malicious requests.

## Via WP-CLI

### Generate and print to stdout (default)

```bash
# Nginx configuration
wp backto:generate-server-config

# Apache configuration
wp backto:generate-server-config --server=apache

# Both at once
wp backto:generate-server-config --server=both
```

### Write to files

```bash
wp backto:generate-server-config --output=file --dir=/etc/nginx/conf.d
wp backto:generate-server-config --server=apache --output=file --dir=/var/www/html
```

This creates `backto-bot-protection.conf` (Nginx) or `.htaccess-bot-protection` (Apache).

### Add extra blocked bots or IPs

```bash
wp backto:generate-server-config --extra-bots="MyCustomBot,AnotherBot"
wp backto:generate-server-config --blocked-ips="192.0.2.0/24,10.0.0.1"
```

## Via PHP

```php
<?php

use BackTo\Framework\Bundle\Security\Network\ServerConfigGenerator;

$generator = new ServerConfigGenerator();

// Customize settings
$generator
    ->setGlobalRateLimit(20, 40)           // 20 req/s, burst of 40
    ->setSensitiveRateLimit(2, 5)          // 2 req/s, burst of 5
    ->setMaxConnectionsPerIp(15)
    ->addBlockedUserAgents(['MyCustomBot'])
    ->setBlockedIps(['192.0.2.0/24']);

// Print the config
echo $generator->generateNginx();
echo $generator->generateApache();

// Or write to disk
$generator->writeNginx('/etc/nginx/conf.d/backto-bot-protection.conf');
$generator->writeApache('/var/www/html/.htaccess-bot-protection');
```

## After generating

### Nginx

Include the generated file in your `server {}` block and reload:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### Apache

Include the generated file or merge it into your existing `.htaccess`:

```bash
cat .htaccess-bot-protection >> /var/www/html/.htaccess
sudo systemctl reload apache2
```

## Default blocked User-Agents

The generator blocks these User-Agents by default: SemrushBot, AhrefsBot, DotBot, MJ12bot, BLEXBot, PetalBot, DataForSeoBot, GPTBot, CCBot.

Use `setBlockedUserAgents()` to replace the list entirely, or `addBlockedUserAgents()` to add to it.

## See also

- [Protect against bot traffic](./protect-against-bot-traffic.md) — full multi-layer protection strategy
- [Customize REST API rate limiting](./customize-rest-api-rate-limiting.md) — application-level rate limiting
- [Set up IP access control](./set-up-ip-access-control.md) — application-level IP blocking
