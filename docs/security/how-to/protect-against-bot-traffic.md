# Proteger le site contre le trafic de bots

Ce guide couvre la mise en place de protections contre les bots de scraping et les crawlers agressifs qui surchargent le serveur en lancant trop de processus PHP. Les etapes vont de l'infrastructure (la plus efficace) a l'application (filet de securite).

## Prerequis

- Acces administrateur au serveur (Nginx ou Apache)
- Un site WordPress utilisant le BackTo Framework avec le Security Bundle active
- (Recommande) Un compte Cloudflare ou un CDN equivalent

## Etape 1 : Activer la protection au niveau CDN

La methode la plus efficace. Le trafic malveillant est bloque avant d'atteindre votre serveur.

### Cloudflare

1. Passez votre domaine sous Cloudflare (proxy active, icone orange).
2. Activez **Bot Fight Mode** dans Security > Bots.
3. Creez une regle de rate limiting dans Security > WAF > Rate limiting rules :
   - Si les requetes d'une meme IP depassent **60 par minute**, appliquer un **challenge** ou **block**.
4. Creez des regles de firewall pour bloquer les bots de scraping connus :
   - Condition : `User-Agent contains "SemrushBot" OR User-Agent contains "AhrefsBot" OR User-Agent contains "DotBot"`
   - Action : **Block**
5. (Optionnel) Activez **Under Attack Mode** temporairement si l'attaque est en cours.

### Page Rules pour le cache

Configurez des Page Rules pour mettre en cache les pages publiques et eviter qu'elles ne touchent PHP :

- URL : `example.com/*`
- Reglage : Cache Level = Cache Everything, Edge Cache TTL = 2 heures

## Etape 2 : Configurer le rate limiting au niveau du serveur web

Le Security Bundle peut generer ces configurations automatiquement via WP-CLI :

```bash
# Generer la config Nginx
wp backto:generate-server-config --server=nginx --output=file --dir=/etc/nginx/conf.d

# Generer la config Apache
wp backto:generate-server-config --server=apache --output=file --dir=/var/www/html
```

Voir [Generate server bot protection config](./generate-server-bot-protection-config.md) pour plus de details.

Si vous preferez configurer manuellement :

### Nginx

Ajoutez dans votre configuration Nginx :

```nginx
# Dans le bloc http {}
limit_req_zone $binary_remote_addr zone=global:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=sensitive:10m rate=2r/s;
limit_conn_zone $binary_remote_addr zone=conn:10m;

server {
    # Limite globale
    location / {
        limit_req zone=global burst=20 nodelay;
        limit_conn conn 20;
        # ... votre configuration existante
    }

    # Endpoints sensibles (login, xmlrpc, cron)
    location ~ ^/(wp-login\.php|xmlrpc\.php|wp-cron\.php) {
        limit_req zone=sensitive burst=3 nodelay;
        # ... votre configuration existante
    }

    # Bloquer les User-Agents de bots de scraping
    if ($http_user_agent ~* (SemrushBot|AhrefsBot|DotBot|MJ12bot|BLEXBot|PetalBot|DataForSeoBot|GPTBot|CCBot)) {
        return 403;
    }

    # Bloquer les requetes sans User-Agent (souvent des scripts)
    if ($http_user_agent = "") {
        return 403;
    }
}
```

Rechargez Nginx :

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### Apache

```apache
# Dans .htaccess ou la config du vhost
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTP_USER_AGENT} (SemrushBot|AhrefsBot|DotBot|MJ12bot|BLEXBot|PetalBot|DataForSeoBot|GPTBot|CCBot) [NC]
    RewriteRule .* - [F,L]
</IfModule>

# Si mod_evasive est disponible
<IfModule mod_evasive24.c>
    DOSHashTableSize    3097
    DOSPageCount        5
    DOSSiteCount        50
    DOSPageInterval     1
    DOSSiteInterval     1
    DOSBlockingPeriod   60
</IfModule>
```

## Etape 3 : Installer fail2ban pour bannir les recidivistes

Creez un filtre personnalise :

```bash
sudo tee /etc/fail2ban/filter.d/wordpress-bot.conf > /dev/null << 'EOF'
[Definition]
failregex = ^<HOST> .* "(GET|POST|HEAD) .*(wp-login|xmlrpc|wp-cron|wp-json).*" (403|429) .*$
ignoreregex =
EOF
```

Ajoutez la jail :

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

Redemarrez fail2ban :

```bash
sudo systemctl restart fail2ban
sudo fail2ban-client status wordpress-bot
```

Toute IP generant plus de 30 requetes en 1 minute vers les endpoints sensibles est bannie 1 heure au niveau du pare-feu.

## Etape 4 : Configurer le Security Bundle (couche applicative)

### Rate limiting REST API

```php
<?php

use BackTo\Framework\Bundle\Security\Network\RestApiRateLimiter;

$rateLimiter = $container->get(RestApiRateLimiter::class);

// Limite globale stricte pour les bots qui atteignent PHP
$rateLimiter->setDefaultLimit(30);
$rateLimiter->setDefaultWindow(60);

// Endpoints sensibles
$rateLimiter->setRouteLimit('/wp/v2/users', 5, 60);
$rateLimiter->setRouteLimit('/jwt-auth/', 3, 300);
```

### Blocage IP

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\IPAccessControlInterface;

$ipControl = $container->get(IPAccessControlInterface::class);

// Bloquer les plages IP identifiees comme sources de bots
$ipControl->addToBlacklist('192.0.2.0/24');

// Restreindre l'acces admin a vos IP
$ipControl->addToWhitelist('203.0.113.10');
```

### Protection des formulaires

Le `CommentSpamProtection` est automatiquement actif avec le Security Bundle. Il bloque les soumissions automatisees via honeypot, validation du referer et analyse du contenu. Voir [Protect comment forms with honeypot and content filtering](./protect-comment-forms-with-honeypot-and-content-filtering.md).

## Etape 5 : Ajuster PHP-FPM pour limiter les degats

Meme avec toutes les protections, ajustez PHP-FPM pour plafonner les processus :

```ini
; /etc/php/8.2/fpm/pool.d/www.conf
pm = ondemand
pm.max_children = 50
pm.process_idle_timeout = 10s
pm.max_requests = 500
request_terminate_timeout = 30s
```

- `pm.max_children = 50` : empeche le serveur de creer des centaines de processus PHP.
- `request_terminate_timeout = 30s` : tue les processus bloques par des pages lourdes.

Rechargez PHP-FPM :

```bash
sudo systemctl reload php8.2-fpm
```

## Etape 6 : Mettre a jour robots.txt

Ajoutez les bots de scraping connus a votre `robots.txt` :

```
User-agent: SemrushBot
Disallow: /

User-agent: AhrefsBot
Disallow: /

User-agent: MJ12bot
Disallow: /

User-agent: DotBot
Disallow: /

User-agent: PetalBot
Disallow: /

User-agent: GPTBot
Disallow: /

User-agent: CCBot
Disallow: /

User-agent: *
Crawl-delay: 10
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
```

Note : les bots malveillants ignorent `robots.txt`. Ce fichier reduit uniquement la charge des bots qui respectent la convention.

## Verification

Apres mise en place, verifiez que les protections fonctionnent :

```bash
# Verifier les logs Nginx pour les 403/429
grep -c " 403 " /var/log/nginx/access.log
grep -c " 429 " /var/log/nginx/access.log

# Verifier les IP bannies par fail2ban
sudo fail2ban-client status wordpress-bot

# Surveiller les processus PHP actifs
ps aux | grep php-fpm | wc -l

# Tester le rate limiting (doit renvoyer 429 apres la limite)
for i in $(seq 1 35); do
    curl -s -o /dev/null -w "%{http_code}\n" https://example.com/wp-json/wp/v2/posts
done
```

## Pour aller plus loin

- [Customize REST API rate limiting](./customize-rest-api-rate-limiting.md)
- [Set up IP access control](./set-up-ip-access-control.md)
- [Restrict REST API access](./restrict-rest-api-access.md)
- [Strategie de protection contre les bots (explication)](../explanation/bot-protection-strategy.md)
