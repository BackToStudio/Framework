# Proteger un site contre les bots avec Apache

*How-to — Oriente tache*

Ce guide couvre la mise en place d'une protection anti-bots complete sur un serveur Apache, de l'infrastructure au serveur web.

## Prerequis

- Acces root/sudo au serveur Apache
- Modules actives : `mod_rewrite`, `mod_evasive` (optionnel), `mod_authz_core`
- Un site WordPress utilisant le BackTo Framework avec le Security Bundle active
- (Recommande) Un compte Cloudflare ou un CDN equivalent

## Etape 1 : Activer la protection au niveau CDN

La methode la plus efficace. Le trafic est bloque avant d'atteindre votre serveur.

1. Passez votre domaine sous Cloudflare (proxy active, icone orange).
2. Activez **Bot Fight Mode** dans Security > Bots.
3. Creez une regle de rate limiting dans Security > WAF > Rate limiting rules :
   - Si les requetes d'une meme IP depassent **60 par minute** → **Block**.
4. Creez des regles de firewall pour bloquer les bots de scraping connus :
   - Condition : `User-Agent contains "SemrushBot" OR "AhrefsBot" OR "DotBot"`
   - Action : **Block**
5. Configurez une Page Rule pour mettre en cache les pages publiques :
   - URL : `example.com/*`
   - Cache Level : Cache Everything, Edge Cache TTL : 2 heures

## Etape 2 : Generer la configuration Apache via le Security Bundle

Le Security Bundle genere la configuration Apache a partir de vos parametres dans `config/security.php` :

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

Generez le fichier de configuration :

```bash
wp backto:generate-server-config --server=apache --output=file --dir=/var/www/html
```

Cela cree `/var/www/html/.htaccess-bot-protection`.

### Integrer dans la configuration Apache

#### Option A : Fusionner dans .htaccess

```bash
cat /var/www/html/.htaccess-bot-protection >> /var/www/html/.htaccess
```

#### Option B : Inclure dans le VirtualHost

```apache
# /etc/apache2/sites-available/example.com.conf
<VirtualHost *:443>
    ServerName example.com
    DocumentRoot /var/www/html

    Include /var/www/html/.htaccess-bot-protection

    # ... reste de la config
</VirtualHost>
```

Rechargez Apache :

```bash
sudo apachectl configtest && sudo systemctl reload apache2
```

### Configuration manuelle (sans le generateur)

Si vous preferez configurer manuellement, ajoutez dans `.htaccess` ou le VirtualHost :

#### Blocage des User-Agents de bots

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Bloquer les bots de scraping connus
    RewriteCond %{HTTP_USER_AGENT} (SemrushBot|AhrefsBot|DotBot|MJ12bot|BLEXBot|PetalBot|DataForSeoBot|GPTBot|CCBot) [NC]
    RewriteRule .* - [F,L]

    # Bloquer les requetes sans User-Agent
    RewriteCond %{HTTP_USER_AGENT} ^$ [NC]
    RewriteRule .* - [F,L]
</IfModule>
```

#### Blocage d'IP specifiques

```apache
# Apache 2.4+ (mod_authz_core)
<RequireAll>
    Require all granted
    Require not ip 192.0.2.0/24
    Require not ip 198.51.100.50
</RequireAll>
```

Pour Apache 2.2 :

```apache
<IfModule !mod_authz_core.c>
    Order allow,deny
    Allow from all
    Deny from 192.0.2.0/24
    Deny from 198.51.100.50
</IfModule>
```

#### Rate limiting avec mod_evasive

```bash
sudo a2enmod evasive
```

```apache
<IfModule mod_evasive24.c>
    DOSHashTableSize    3097
    DOSPageCount        5         # max 5 requetes sur la meme page/seconde
    DOSSiteCount        50        # max 50 requetes sur le site/seconde
    DOSPageInterval     1
    DOSSiteInterval     1
    DOSBlockingPeriod   60        # bannir 60 secondes
    DOSEmailNotify      admin@example.com
    DOSLogDir           "/var/log/mod_evasive"
</IfModule>
```

```bash
sudo mkdir -p /var/log/mod_evasive
sudo chown www-data:www-data /var/log/mod_evasive
```

#### Protection des endpoints sensibles

```apache
# Rate limiting specifique pour wp-login.php
<Files "wp-login.php">
    <IfModule mod_evasive24.c>
        DOSPageCount    3
        DOSPageInterval 1
    </IfModule>
</Files>

# Bloquer xmlrpc.php completement (si non utilise)
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

## Etape 3 : Installer fail2ban pour bannir les recidivistes

Creez un filtre qui detecte les IP bloquees dans les logs Apache :

```bash
sudo tee /etc/fail2ban/filter.d/wordpress-bot.conf > /dev/null << 'EOF'
[Definition]
failregex = ^<HOST> .* "(GET|POST|HEAD) .*(wp-login|xmlrpc|wp-cron|wp-json).*" (403|429) .*$
ignoreregex =
EOF
```

Ajoutez la jail (adaptez le chemin des logs) :

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

Toute IP generant plus de 30 requetes bloquees en 1 minute est bannie 1 heure au niveau du pare-feu.

## Etape 4 : Configurer la couche applicative

Voir [Configurer la protection applicative contre les bots](./configure-application-bot-protection.md).

## Etape 5 : Ajuster PHP

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

## Modules Apache recommandes

| Module | Role | Commande d'activation |
|---|---|---|
| `mod_rewrite` | Blocage par User-Agent, redirection | `sudo a2enmod rewrite` |
| `mod_evasive` | Rate limiting / anti-DDoS | `sudo apt install libapache2-mod-evasive` |
| `mod_security2` | WAF (Web Application Firewall) | `sudo apt install libapache2-mod-security2` |
| `mod_authz_core` | Controle d'acces IP (Apache 2.4+) | Active par defaut |
| `mod_headers` | Headers de securite | `sudo a2enmod headers` |

## Verification

```bash
# Verifier les 403 dans les logs Apache
grep -c " 403 " /var/log/apache2/access.log

# IP bannies par fail2ban
sudo fail2ban-client status wordpress-bot

# Processus PHP actifs
ps aux | grep php | wc -l

# Tester le blocage User-Agent
curl -s -o /dev/null -w "%{http_code}\n" -A "SemrushBot" https://example.com/
# Doit renvoyer 403
```

## Voir aussi

- [Proteger un site contre les bots avec Nginx](./protect-against-bots-nginx.md)
- [Configurer la protection applicative contre les bots](./configure-application-bot-protection.md)
- [Generer la configuration serveur](./generate-server-bot-protection-config.md)
- [Strategie de protection contre les bots (explication)](../explanation/bot-protection-strategy.md)
