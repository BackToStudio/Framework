# Strategie de protection contre les bots

*Explanation — Oriente comprehension*

## Le probleme

Les bots de scraping, les crawlers agressifs et les outils d'attaque automatises representent une menace directe pour la disponibilite d'un site WordPress. Chaque requete bot declenche un processus PHP, consomme de la memoire et sollicite la base de donnees. Quand des centaines de bots frappent simultanement, le serveur sature : les processus PHP s'accumulent, les workers sont epuises, et le site devient inaccessible pour les vrais utilisateurs.

## Le principe : bloquer le plus tot possible

La strategie de defense suit un principe simple : **chaque couche intercepte le trafic avant qu'il n'atteigne la suivante**. Plus on bloque tot, moins le serveur travaille.

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
  |                               Bannit les recidivistes au pare-feu
  v
[Couche 4 — Application]          BackTo Framework Security Bundle
                                  Rate limiting, IP control, honeypot
```

Ce modele s'inscrit dans le principe de [defense en profondeur](./defense-in-depth.md) deja applique par le Security Bundle.

## Couche 1 — Reseau / CDN

C'est la couche la plus efficace. Le trafic malveillant est bloque **avant d'atteindre votre serveur**. Aucun processus PHP n'est lance, aucune bande passante serveur n'est consommee.

**Cloudflare** (recommande) offre :
- **Bot Fight Mode** : identification via empreinte TLS (JA3/JA4), reputation IP, analyse comportementale.
- **WAF Managed Rules** : regles preconfigurees (SQLi, XSS, path traversal).
- **Rate Limiting** : limitation par IP avant le serveur.
- **Firewall Rules** : blocage par pays, ASN, User-Agent, ou URI.
- **Caching** : pages servies directement par le CDN sans toucher au serveur.

**Alternatives** : AWS CloudFront + WAF, Sucuri Firewall, Fastly.

**Pourquoi c'est la priorite** : un bot bloque au CDN ne consomme aucune ressource serveur. C'est le meilleur ratio cout/efficacite.

## Couche 2 — Serveur web

Si un bot passe le CDN (ou si vous n'en avez pas), le serveur web peut le bloquer **avant de lancer PHP**. C'est crucial car c'est le lancement de processus PHP qui sature le serveur.

### Nginx

Nginx gere le rate limiting en memoire via des zones partagees (`limit_req_zone`). Chaque zone stocke un compteur par IP. Les requetes excessives sont rejetees avec HTTP 429 sans jamais invoquer le gestionnaire FastCGI/PHP.

Les mecanismes disponibles :
- **`limit_req_zone`** : limitation du debit par IP (requetes/seconde avec burst).
- **`limit_conn_zone`** : limitation des connexions simultanees par IP.
- **`if ($http_user_agent)`** : blocage par User-Agent via regex.
- **`deny`** : blocage par IP/CIDR.

### Apache

Apache dispose de plusieurs modules pour le meme objectif :
- **`mod_rewrite`** : blocage par User-Agent via `RewriteCond` (natif, toujours disponible).
- **`mod_evasive`** : rate limiting leger avec detection de burst (module additionnel).
- **`mod_security2`** : WAF complet avec regles OWASP (module additionnel).
- **`mod_authz_core`** : controle d'acces IP via `Require not ip` (natif, Apache 2.4+).

### Generateur de configuration

Le `ServerConfigGenerator` du Security Bundle genere les configurations Nginx et Apache a partir des parametres `config/security.php`. L'interet est double :
1. **Source unique de verite** : les memes parametres (User-Agents, IPs, rate limits) alimentent la couche serveur et la couche applicative.
2. **Reproductibilite** : la configuration serveur est regeneree a chaque changement de parametres via `wp backto:generate-server-config`.

## Couche 3 — Systeme

`fail2ban` surveille les logs du serveur web et bannit les IP recidivistes au niveau du pare-feu (iptables/nftables). L'IP est bloquee avant meme que le serveur web ne traite la requete.

Le mecanisme :
1. fail2ban lit les logs d'acces en continu.
2. Un filtre regex identifie les requetes bloquees (403, 429) vers les endpoints sensibles.
3. Quand une IP depasse le seuil (ex : 30 requetes en 1 minute), une regle iptables la bloque.
4. Apres le delai de bannissement (ex : 1 heure), la regle est supprimee.

C'est complementaire au rate limiting du serveur web : Nginx/Apache bloque chaque requete individuelle, fail2ban bannit les recidivistes.

## Couche 4 — Application

Si un bot arrive jusqu'a PHP malgre les couches precedentes, le Security Bundle prend le relais avec :
- **`RestApiRateLimiter`** : rate limiting par IP sur les endpoints REST (HTTP 429).
- **`IPAccessControl`** : whitelist/blacklist applicatif avec support CIDR.
- **`CommentSpamProtection`** : honeypot, validation referer, analyse contenu.
- **`RestApiSecurity`** : authentification requise sur l'API REST.

Cette couche lance un processus PHP, mais le coupe rapidement (429/403 sans traitement lourd).

## Impact sur les processus PHP

| Couche | Processus PHP lance ? | Cout serveur |
|---|---|---|
| CDN (Cloudflare) | Non | Nul |
| Serveur web (Nginx/Apache) | Non | Negligeable |
| Systeme (fail2ban/iptables) | Non | Nul |
| Application (Security Bundle) | Oui, mais coupe rapidement | Faible |

L'objectif est de **reduire au maximum le nombre de requetes qui atteignent PHP**. Chaque couche agit comme un filtre progressif.

## Configuration PHP-FPM

En complement des couches de defense, ajustez PHP-FPM pour resister aux pics :

```ini
; /etc/php/8.2/fpm/pool.d/www.conf
pm = ondemand
pm.max_children = 50
pm.process_idle_timeout = 10s
pm.max_requests = 500
request_terminate_timeout = 30s
```

- **`pm = ondemand`** : ne lance des workers que quand il y a des requetes.
- **`pm.max_children`** : plafonne le nombre de processus PHP simultanees. Ajustez selon votre RAM (environ 30-50 Mo par worker).
- **`pm.max_requests = 500`** : recycle les workers pour eviter les fuites memoire.
- **`request_terminate_timeout = 30s`** : tue les processus qui mettent trop longtemps.

## Robots.txt

Le fichier `robots.txt` ne bloque pas les bots malveillants (ils l'ignorent), mais il reduit la charge des bots legitimes qui respectent la convention. C'est un signal passif, utile mais insuffisant seul.

## Ordre de mise en place recommande

1. **Cloudflare (ou CDN equivalent)** — impact immediat, aucun changement serveur.
2. **Rate limiting Nginx/Apache** — bloque sans PHP, configuration rapide.
3. **fail2ban** — bannit les recidivistes automatiquement.
4. **Security Bundle (rate limiter + IP control)** — filet de securite applicatif.
5. **Ajustement PHP-FPM** — resilience en cas de pic.
6. **Monitoring** — `SecurityAuditLogger` pour detecter les patterns et ajuster les regles.

Chaque couche renforce les autres. Aucune n'est suffisante seule.

## Voir aussi

- [Defense in depth](./defense-in-depth.md)
- [Proteger un site contre les bots avec Nginx](../how-to/protect-against-bots-nginx.md)
- [Proteger un site contre les bots avec Apache](../how-to/protect-against-bots-apache.md)
- [Configurer la protection applicative](../how-to/configure-application-bot-protection.md)
- [ServerConfigGenerator — Reference](../reference/server-config-generator.md)
