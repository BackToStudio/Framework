# Strategie de protection contre les bots

Les bots de scraping, les crawlers agressifs et les outils d'attaque automatises representent une menace directe pour la disponibilite d'un site WordPress. Chaque requete bot declenche un processus PHP, consomme de la memoire et sollicite la base de donnees. Quand des centaines de bots frappent simultanement, le serveur sature : les processus PHP s'accumulent, les workers sont epuises, et le site devient inaccessible pour les vrais utilisateurs.

La strategie de defense suit le principe **"bloquer le plus tot possible"** : chaque couche intercepte le trafic avant qu'il n'atteigne la suivante. Plus on bloque tot, moins le serveur travaille.

## Les 4 couches de defense

```
Requete entrante
  |
  v
[Couche 1 — Reseau / CDN]        Cloudflare, AWS WAF, etc.
  |                               Bloque avant meme d'atteindre le serveur
  v
[Couche 2 — Serveur web]          Nginx / Apache
  |                               Bloque sans lancer PHP
  v
[Couche 3 — Systeme]              fail2ban, ModSecurity
  |                               Bannit les recidivistes
  v
[Couche 4 — Application]          BackTo Framework Security Bundle
                                  Rate limiting, IP control, honeypot
```

### Couche 1 — Reseau / CDN (aucun processus PHP)

C'est la couche la plus efficace. Le trafic malveillant est bloque **avant d'atteindre votre serveur**. Aucun processus PHP n'est lance, aucune bande passante serveur n'est consommee.

**Cloudflare (recommande)** :
- **Bot Fight Mode** : identifie et bloque automatiquement les bots connus via empreinte TLS (JA3/JA4), reputation IP et analyse comportementale.
- **WAF Managed Rules** : regles preconfigures contre les attaques courantes (SQLi, XSS, path traversal).
- **Rate Limiting** : limitez les requetes par IP avant qu'elles n'arrivent au serveur (ex : 60 req/min par IP).
- **Super Bot Fight Mode** (Pro+) : classification granulaire bots verifies / bots automatises / bots probables.
- **Firewall Rules** : bloquez par pays, ASN, User-Agent, ou URI. Ex : bloquer les requetes `wp-login.php` depuis des pays ou vous n'avez pas d'utilisateurs.
- **Under Attack Mode** : en cas d'attaque active, active un challenge JavaScript interstitiel.
- **Caching** : les pages mises en cache sont servies directement par le CDN, sans toucher au serveur.

**Alternatives** : AWS CloudFront + WAF, Sucuri Firewall, Fastly.

**Pourquoi c'est la priorite** : un bot bloque au niveau CDN ne consomme aucune ressource serveur. C'est le meilleur ratio cout/efficacite.

### Couche 2 — Serveur web (aucun processus PHP)

Si un bot passe le CDN (ou si vous n'en avez pas), le serveur web peut le bloquer **avant de lancer PHP**. C'est crucial car c'est le lancement de processus PHP qui sature le serveur.

#### Nginx

```nginx
# Limiter le debit global par IP (10 requetes/seconde, burst de 20)
limit_req_zone $binary_remote_addr zone=bot_limit:10m rate=10r/s;

server {
    # Appliquer la limite
    location / {
        limit_req zone=bot_limit burst=20 nodelay;
        # ... proxy_pass ou fastcgi_pass
    }

    # Protection renforcee sur les endpoints sensibles
    location ~ ^/(wp-login\.php|xmlrpc\.php) {
        limit_req zone=bot_limit burst=3 nodelay;
        # ... proxy_pass ou fastcgi_pass
    }

    # Bloquer les User-Agents de bots connus
    if ($http_user_agent ~* (SemrushBot|AhrefsBot|DotBot|MJ12bot|BLEXBot|PetalBot)) {
        return 403;
    }

    # Bloquer les requetes sans User-Agent
    if ($http_user_agent = "") {
        return 403;
    }

    # Limiter les connexions simultanees par IP
    limit_conn_zone $binary_remote_addr zone=conn_limit:10m;
    limit_conn conn_limit 20;
}
```

#### Apache

```apache
# mod_evasive — protection anti-DDoS legere
<IfModule mod_evasive24.c>
    DOSHashTableSize    3097
    DOSPageCount        5        # max 5 requetes sur la meme page
    DOSSiteCount        50       # max 50 requetes sur le site
    DOSPageInterval     1        # par seconde
    DOSSiteInterval     1        # par seconde
    DOSBlockingPeriod   60       # bannir 60 secondes
</IfModule>

# Bloquer les User-Agents de bots connus
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTP_USER_AGENT} (SemrushBot|AhrefsBot|DotBot|MJ12bot|BLEXBot|PetalBot) [NC]
    RewriteRule .* - [F,L]
</IfModule>
```

**Avantage** : Nginx/Apache gerent ces regles en memoire, sans jamais invoquer PHP. Le cout CPU est negligeable.

### Couche 3 — Systeme (bannissement dynamique)

`fail2ban` surveille les logs et bannit automatiquement les IP recidivistes au niveau du pare-feu (iptables/nftables). L'IP est bloquee avant meme que le serveur web ne traite la requete.

```ini
# /etc/fail2ban/jail.local
[wordpress-bot]
enabled  = true
port     = http,https
filter   = wordpress-bot
logpath  = /var/log/nginx/access.log
maxretry = 30
findtime = 60
bantime  = 3600
action   = iptables-multiport[name=wordpress-bot, port="http,https", protocol=tcp]
```

```ini
# /etc/fail2ban/filter.d/wordpress-bot.conf
[Definition]
failregex = ^<HOST> .* "(GET|POST|HEAD) .*(wp-login|xmlrpc|wp-cron|wp-json).*" (403|429) .*$
ignoreregex =
```

Cela bannit pendant 1 heure toute IP qui genere plus de 30 requetes en 1 minute vers les endpoints sensibles.

### Couche 4 — Application (BackTo Framework)

Si un bot arrive jusqu'a PHP malgre les couches precedentes, le Security Bundle prend le relais.

#### Rate limiting REST API

```php
use BackTo\Framework\Bundle\Security\Network\RestApiRateLimiter;

$rateLimiter = $container->get(RestApiRateLimiter::class);

// Limite globale stricte
$rateLimiter->setDefaultLimit(30);
$rateLimiter->setDefaultWindow(60);

// Endpoints sensibles : limites encore plus basses
$rateLimiter->setRouteLimit('/wp/v2/users', 5, 60);
$rateLimiter->setRouteLimit('/jwt-auth/', 3, 300);
$rateLimiter->setRouteLimit('/wp/v2/posts', 60, 60);
```

#### Blocage IP applicatif

```php
use BackTo\Framework\Bundle\Security\Contracts\IPAccessControlInterface;

$ipControl = $container->get(IPAccessControlInterface::class);

// Bloquer les IP identifiees comme botnets
$ipControl->addToBlacklist('192.0.2.0/24');
$ipControl->addToBlacklist('198.51.100.50');

// Ou restreindre l'admin a vos IP uniquement
$ipControl->addToWhitelist('203.0.113.10');
```

#### Protection anti-spam (formulaires)

Le `CommentSpamProtection` utilise un honeypot, une validation du referer et une analyse du contenu pour bloquer les soumissions automatisees sans impacter les utilisateurs.

## Impact sur les processus PHP

Le probleme initial : **les bots lancent trop de processus PHP**.

| Couche | Processus PHP lance ? | Cout serveur |
|---|---|---|
| CDN (Cloudflare) | Non | Nul |
| Serveur web (Nginx/Apache) | Non | Negligeable |
| Systeme (fail2ban/iptables) | Non | Nul |
| Application (Security Bundle) | Oui, mais coupe rapidement | Faible |

L'objectif est de **reduire au maximum le nombre de requetes qui atteignent PHP**. Chaque couche agit comme un filtre : plus le trafic est filtre en amont, moins PHP travaille.

### Configuration PHP-FPM recommandee

En complement des couches de defense, ajustez PHP-FPM pour resister aux pics :

```ini
; /etc/php/8.2/fpm/pool.d/www.conf

; Utiliser le mode 'ondemand' pour ne creer des workers qu'en cas de besoin
pm = ondemand
pm.max_children = 50
pm.process_idle_timeout = 10s
pm.max_requests = 500

; Limiter la duree d'execution pour eviter les workers bloques
request_terminate_timeout = 30s
```

- `pm = ondemand` : ne lance des workers que quand il y a des requetes (reduit la memoire au repos).
- `pm.max_children` : plafonne le nombre de processus PHP simultanesS. Ajustez selon votre RAM (environ 30-50 Mo par worker).
- `pm.max_requests = 500` : recycle les workers pour eviter les fuites memoire.
- `request_terminate_timeout = 30s` : tue les processus qui mettent trop longtemps (les bots qui crawlent des pages lourdes).

## Robots.txt et signaux passifs

Le fichier `robots.txt` ne bloque pas les bots malveillants (ils l'ignorent), mais il reduit la charge des bots legitimes qui le respectent :

```
User-agent: SemrushBot
Disallow: /

User-agent: AhrefsBot
Disallow: /

User-agent: *
Crawl-delay: 10
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
```

C'est un signal passif : utile mais insuffisant seul.

## Ordre de mise en place recommande

1. **Cloudflare (ou CDN equivalent)** — impact immediat, aucun changement serveur.
2. **Rate limiting Nginx/Apache** — bloque sans PHP, configuration rapide.
3. **fail2ban** — bannit les recidivistes automatiquement.
4. **Security Bundle (rate limiter + IP control)** — filet de securite applicatif.
5. **Ajustement PHP-FPM** — resilience en cas de pic.
6. **Monitoring** — utilisez le `SecurityAuditLogger` du bundle pour detecter les patterns et ajuster les regles.

Chaque couche renforce les autres. Aucune n'est suffisante seule.
