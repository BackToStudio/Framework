# Proteger un site contre les bots avec Nginx

*How-to — Oriente tache*

Ce guide couvre la mise en place d'une protection anti-bots complete sur un serveur Nginx, de l'infrastructure au serveur web.

## Prerequis

- Acces root/sudo au serveur Nginx
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

## Etape 2 : Generer la configuration Nginx via le Security Bundle

Le Security Bundle genere la configuration Nginx a partir de vos parametres dans `config/security.php` :

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
wp backto:generate-server-config --server=nginx --output=file --dir=/etc/nginx/conf.d
```

Cela cree `/etc/nginx/conf.d/backto-bot-protection.conf`.

### Integrer dans la configuration Nginx

Le fichier genere contient deux sections. La premiere (les `limit_req_zone`) doit etre dans le bloc `http {}`. La seconde (les `location`, `if`, `deny`) dans le bloc `server {}` :

```nginx
# /etc/nginx/nginx.conf — bloc http {}
http {
    include /etc/nginx/conf.d/backto-bot-protection.conf;
    # ... reste de la config
}
```

Ou, si vous preferez separer :

```nginx
# /etc/nginx/nginx.conf — bloc http {}
http {
    # Zones de rate limiting
    limit_req_zone $binary_remote_addr zone=backto_global:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=backto_sensitive:10m rate=2r/s;
    limit_conn_zone $binary_remote_addr zone=backto_conn:10m;

    # ...
}
```

```nginx
# /etc/nginx/sites-available/example.com — bloc server {}
server {
    # Bloquer les User-Agents de bots
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

Validez et rechargez :

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### Configuration manuelle (sans le generateur)

Si vous ne souhaitez pas utiliser le generateur, copiez la configuration ci-dessus directement dans vos fichiers Nginx.

## Etape 3 : Installer fail2ban pour bannir les recidivistes

Creez un filtre qui detecte les IP bloquees dans les logs Nginx :

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

```bash
sudo systemctl restart fail2ban
```

Toute IP generant plus de 30 requetes bloquees en 1 minute est bannie 1 heure au niveau du pare-feu (iptables).

## Etape 4 : Configurer la couche applicative

Voir [Configurer la protection applicative contre les bots](./configure-application-bot-protection.md).

## Etape 5 : Ajuster PHP-FPM

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
# Verifier les 403/429 dans les logs Nginx
grep -c " 403 " /var/log/nginx/access.log
grep -c " 429 " /var/log/nginx/access.log

# IP bannies par fail2ban
sudo fail2ban-client status wordpress-bot

# Processus PHP actifs
ps aux | grep php-fpm | wc -l

# Tester le rate limiting (doit renvoyer 429 apres la limite)
for i in $(seq 1 35); do
    curl -s -o /dev/null -w "%{http_code}\n" https://example.com/wp-json/wp/v2/posts
done
```

## Voir aussi

- [Proteger un site contre les bots avec Apache](./protect-against-bots-apache.md)
- [Configurer la protection applicative contre les bots](./configure-application-bot-protection.md)
- [Generer la configuration serveur](./generate-server-bot-protection-config.md)
- [Strategie de protection contre les bots (explication)](../explanation/bot-protection-strategy.md)
