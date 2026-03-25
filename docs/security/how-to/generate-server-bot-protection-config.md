# Generate server bot protection configuration

*How-to — Task-oriented*

The `ServerConfigGenerator` produces Nginx and Apache configuration snippets that block bot traffic at the web-server level, before PHP is invoked. This eliminates the overhead of launching PHP processes for malicious requests.

## Configure via config/security.php

The generator reads its settings from the Security Bundle configuration. Define your bot protection rules once, then generate server configs from them:

```php
<?php
// config/security.php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        // User-Agents to block at the server level
        ->botBlockedUserAgents([
            'SemrushBot',
            'AhrefsBot',
            'DotBot',
            'MJ12bot',
            'BLEXBot',
            'PetalBot',
            'DataForSeoBot',
            'GPTBot',
            'CCBot',
        ])

        // IP addresses or CIDR ranges to deny
        ->botBlockedIps([
            '192.0.2.0/24',
            '198.51.100.50',
        ])

        // Endpoints with stricter rate limits
        ->botSensitiveEndpoints([
            'wp-login.php',
            'xmlrpc.php',
            'wp-cron.php',
        ])

        // Global rate limiting: requests/second and burst
        ->botGlobalRateLimit(10, 20)

        // Stricter rate limiting for sensitive endpoints
        ->botSensitiveRateLimit(2, 3)

        // Max simultaneous connections per IP
        ->botMaxConnectionsPerIp(20)

        // Block requests with no User-Agent header
        ->botBlockEmptyUserAgent(true);
};
```

All values have sensible defaults (see `SecurityConfiguration::getDefaults()`). You only need to override what you want to change.

## Generate via WP-CLI

The command reads the configuration defined above and generates the corresponding server config:

```bash
# Nginx configuration (printed to stdout)
wp backto:generate-server-config

# Apache configuration
wp backto:generate-server-config --server=apache

# Both at once
wp backto:generate-server-config --server=both

# Write to files instead of stdout
wp backto:generate-server-config --output=file --dir=/etc/nginx/conf.d
wp backto:generate-server-config --server=apache --output=file --dir=/var/www/html
```

This creates `backto-bot-protection.conf` (Nginx) or `.htaccess-bot-protection` (Apache).

### Override config via CLI flags

CLI flags override the configuration for a single generation. Useful for testing or one-off additions:

```bash
# Add extra bots beyond the configured list
wp backto:generate-server-config --extra-bots="MyCustomBot,AnotherBot"

# Add extra blocked IPs
wp backto:generate-server-config --blocked-ips="10.0.0.0/8"
```

## Generate via PHP

When the Security Bundle is active, the `ServerConfigGenerator` is pre-configured from the container parameters:

```php
<?php

use BackTo\Framework\Bundle\Security\Network\ServerConfigGenerator;

// Service is already configured from config/security.php
$generator = $container->get(ServerConfigGenerator::class);

// Generate and write
$generator->writeNginx('/etc/nginx/conf.d/backto-bot-protection.conf');
$generator->writeApache('/var/www/html/.htaccess-bot-protection');

// Or print
echo $generator->generateNginx();

// Inspect current configuration
$config = $generator->getConfiguration();
// Returns: blocked_user_agents, blocked_ips, sensitive_endpoints,
//          global_rate_limit, global_burst, sensitive_rate_limit,
//          sensitive_burst, max_connections_per_ip, block_empty_user_agent
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

## Workflow

1. Define your bot protection rules in `config/security.php`
2. Run `wp backto:generate-server-config --output=file` to generate the config
3. Include the generated file in your Nginx/Apache configuration
4. Reload the web server
5. When you update the config, re-run the command and reload

The configuration is the single source of truth. The generated server config always reflects what you defined in `config/security.php`.

## See also

- [Protect against bots with Nginx](./protect-against-bots-nginx.md)
- [Protect against bots with Apache](./protect-against-bots-apache.md)
- [Configure application bot protection](./configure-application-bot-protection.md)
- [ServerConfigGenerator — Reference](../reference/server-config-generator.md)
