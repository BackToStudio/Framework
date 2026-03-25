# Configurer la protection applicative contre les bots

*How-to — Oriente tache*

Ce guide couvre la couche applicative de la protection anti-bots : le Security Bundle du BackTo Framework. Cette couche intervient quand un bot atteint PHP malgre les protections CDN, serveur web et systeme.

## Prerequis

- Un site WordPress utilisant le BackTo Framework
- Le Security Bundle active (voir [Getting started](../tutorial.md))

## Configurer la protection dans config/security.php

Toute la configuration bot se definit dans `config/security.php`. Ces parametres alimentent a la fois la couche applicative (rate limiter, IP control) et le generateur de configuration serveur.

```php
<?php
// config/security.php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        // Bots bloques au niveau serveur ET applicatif
        ->botBlockedUserAgents([
            'SemrushBot', 'AhrefsBot', 'DotBot', 'MJ12bot',
            'BLEXBot', 'PetalBot', 'DataForSeoBot', 'GPTBot', 'CCBot',
        ])

        // IP/CIDR a bloquer
        ->botBlockedIps(['192.0.2.0/24'])

        // Endpoints sensibles (rate limiting renforce)
        ->botSensitiveEndpoints(['wp-login.php', 'xmlrpc.php', 'wp-cron.php'])

        // Rate limiting global et sensible
        ->botGlobalRateLimit(10, 20)
        ->botSensitiveRateLimit(2, 3)

        // Connexions simultanees par IP
        ->botMaxConnectionsPerIp(20)

        // Bloquer les requetes sans User-Agent
        ->botBlockEmptyUserAgent(true)

        // REST API : authentification requise
        ->restApiRequireAuth(true);
};
```

## Rate limiting REST API

Le `RestApiRateLimiter` limite les requetes par IP sur les endpoints REST. Il renvoie HTTP 429 avec les headers `X-RateLimit-Limit`, `X-RateLimit-Remaining` et `Retry-After`.

```php
<?php

use BackTo\Framework\Bundle\Security\Network\RestApiRateLimiter;

$rateLimiter = $container->get(RestApiRateLimiter::class);

// Limite globale
$rateLimiter->setDefaultLimit(30);
$rateLimiter->setDefaultWindow(60);

// Limites par route
$rateLimiter->setRouteLimit('/wp/v2/users', 5, 60);
$rateLimiter->setRouteLimit('/jwt-auth/', 3, 300);
$rateLimiter->setRouteLimit('/wp/v2/posts', 60, 60);
```

## Controle d'acces IP

Le `IPAccessControl` gere les listes blanches et noires au niveau applicatif. Supporte la notation CIDR (IPv4 et IPv6).

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\IPAccessControlInterface;

$ipControl = $container->get(IPAccessControlInterface::class);

// Bloquer des IP
$ipControl->addToBlacklist('192.0.2.0/24');
$ipControl->addToBlacklist('198.51.100.50');

// Restreindre l'admin a des IP specifiques
$ipControl->addToWhitelist('203.0.113.10');
$ipControl->addToWhitelist('198.51.100.0/24');
```

Quand une whitelist est definie, seules ces IP accedent a `/wp-admin` et `/wp-login.php`. Les autres recoivent HTTP 403.

## Protection des formulaires

Le `CommentSpamProtection` bloque les soumissions automatisees via trois techniques :

1. **Honeypot** : un champ cache que les bots remplissent mais que les humains ne voient pas.
2. **Validation du referer** : rejette les soumissions ne provenant pas de votre site.
3. **Analyse du contenu** : bloque les commentaires avec trop de liens ou du HTML dangereux.

```php
<?php

use BackTo\Framework\Bundle\Security\Hardening\CommentSpamProtection;

$spam = $container->get(CommentSpamProtection::class);

// Maximum 1 lien par commentaire (defaut : 2)
$spam->setMaxLinksAllowed(1);
```

Voir [Protect comment forms with honeypot and content filtering](./protect-comment-forms-with-honeypot-and-content-filtering.md).

## Generer la configuration serveur

Une fois `config/security.php` configure, generez les regles Nginx ou Apache correspondantes :

```bash
# Nginx
wp backto:generate-server-config --server=nginx --output=file --dir=/etc/nginx/conf.d

# Apache
wp backto:generate-server-config --server=apache --output=file --dir=/var/www/html

# Les deux
wp backto:generate-server-config --server=both --output=file
```

Voir [Generer la configuration serveur](./generate-server-bot-protection-config.md).

## Monitoring

Utilisez le `SecurityAuditLogger` pour suivre les tentatives bloquees et ajuster les regles :

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\AuditLogRepositoryInterface;

$auditLog = $container->get(AuditLogRepositoryInterface::class);

// Consulter les evenements recents
$events = $auditLog->findRecent(50);
```

Les evenements de rate limiting et de blocage IP sont enregistres automatiquement avec l'IP source, l'endpoint cible et le timestamp.

## Voir aussi

- [Proteger un site contre les bots avec Nginx](./protect-against-bots-nginx.md)
- [Proteger un site contre les bots avec Apache](./protect-against-bots-apache.md)
- [Customize REST API rate limiting](./customize-rest-api-rate-limiting.md)
- [Set up IP access control](./set-up-ip-access-control.md)
- [Restrict REST API access](./restrict-rest-api-access.md)
