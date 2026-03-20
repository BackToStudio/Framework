# Guides pratiques Security

Recettes concretes pour les cas d'utilisation courants du bundle Security.

---

## Configurer les en-tetes Content Security Policy (CSP)

Le `ContentSecurityPolicyManager` envoie un en-tete CSP avec des nonces automatiques pour les scripts.

### Ajouter des sources autorisees

```php
$csp = $container->get(ContentSecurityPolicyInterface::class);

$csp->addDirective('script-src', 'https://cdn.example.com');
$csp->addDirective('style-src', ['https://fonts.googleapis.com', "'unsafe-inline'"]);
$csp->addDirective('img-src', ['https:', 'data:']);
$csp->addDirective('connect-src', 'https://api.example.com');
$csp->addDirective('frame-ancestors', "'none'");
```

### Activer le mode report-only pour tester

```php
$security->cspReportOnly(true);
```

En mode report-only, l'en-tete envoye est `Content-Security-Policy-Report-Only` au lieu de `Content-Security-Policy`. Les violations sont signalees sans bloquer les ressources.

### Directives par defaut

| Directive | Valeur par defaut |
|-----------|-------------------|
| `default-src` | `'self'` |
| `script-src` | `'self'` + nonce automatique |
| `style-src` | `'self' 'unsafe-inline'` |
| `img-src` | `'self' data: https:` |
| `font-src` | `'self' data:` |
| `connect-src` | `'self'` |
| `frame-ancestors` | `'self'` |
| `base-uri` | `'self'` |
| `form-action` | `'self'` |

---

## Mettre en place le controle d'acces IP

Le `IPAccessControl` supporte les modes whitelist et blacklist avec notation CIDR (IPv4 et IPv6).

### Restreindre l'acces admin a des IPs specifiques

```php
$ipControl = $container->get(IPAccessControlInterface::class);

// Mode whitelist : seules ces IPs accedent a /wp-admin et /wp-login.php
$ipControl->addToWhitelist('203.0.113.10');
$ipControl->addToWhitelist('198.51.100.0/24');
```

### Bloquer des IPs specifiques

```php
// Mode blacklist : ces IPs sont refusees
$ipControl->addToBlacklist('192.0.2.50');
$ipControl->addToBlacklist('2001:db8::/32');
```

**Regle de priorite** : si une whitelist est definie, seules les IPs en whitelist sont autorisees. Sinon, toutes les IPs sont autorisees sauf celles en blacklist.

Les hooks `admin_init` et `login_init` verifient l'acces. Les IPs refusees recoivent un HTTP 403.

---

## Personnaliser le rate limiting REST API

Le `RestApiRateLimiter` applique des limites par IP et par route sur le filtre `rest_pre_dispatch`.

### Modifier les limites globales

```php
$rateLimiter = $container->get(RestApiRateLimiter::class);

// 100 requetes par fenetre de 120 secondes (par defaut : 60/60s)
$rateLimiter->setDefaultLimit(100);
$rateLimiter->setDefaultWindow(120);
```

### Definir des limites par route

```php
// Limite stricte sur l'endpoint d'authentification
$rateLimiter->setRouteLimit('/jwt-auth/', 5, 300);

// Limite souple sur les lectures publiques
$rateLimiter->setRouteLimit('/wp/v2/posts', 200, 60);
```

### En-tetes de reponse

Le rate limiter envoie automatiquement :

- `X-RateLimit-Limit` : limite configuree
- `X-RateLimit-Remaining` : requetes restantes
- `Retry-After` : secondes avant reinitialisation (en cas de depassement, HTTP 429)

---

## Activer le journal d'audit de securite

Le `SecurityAuditLogger` est actif par defaut et capture :

| Evenement | Severite |
|-----------|----------|
| `login_success` | Info |
| `login_failed` | Warning |
| `user_role_changed` | Warning |
| `critical_option_changed` | Warning |
| `plugin_activated` / `plugin_deactivated` | Info |
| `theme_switched` | Info |
| `user_created` | Info |
| `user_deleted` | Warning |

### Consulter les logs

**Via l'admin** : menu "Audit Log" (dashicons-shield), filtrage par evenement et severite, pagination.

**Via REST API** :

```
GET /wp-json/backto/v1/security/audit-log?event=login_failed&severity=warning&per_page=50&page=1
```

### Exporter en CSV

Dans la page d'administration Audit Log, cliquez sur "Export". Le fichier CSV contient timestamp, evenement, severite et contexte JSON. Un cooldown de 60 secondes empeche les exports abusifs.

### Purger les anciens evenements

```php
$auditLogger = $container->get(SecurityAuditLogger::class);
$purged = $auditLogger->purgeOldEvents(90); // Supprime les evenements > 90 jours
```

### Configurer les notifications email

Le `SecurityNotifier` envoie des alertes email pour les evenements critiques :

```php
$notifier = $container->get(SecurityNotifierInterface::class);

$notifier->setRecipients(['admin@example.com', 'security@example.com']);
$notifier->setFailedLoginThreshold(5); // Alerte apres 5 echecs de connexion en 10 min
$notifier->addCriticalEvent('custom_event');
```

Evenements critiques par defaut : `login_anomaly`, `self_promotion_blocked`, `file_integrity_failure`, `malware_detected`, `critical_option_changed`, `privileged_role_granted`, `user_role_changed`, `login_failed_threshold`.

---

## Configurer CORS pour WordPress headless

Le `CorsManager` fournit un controle granulaire des en-tetes CORS pour les architectures headless/SPA.

```php
$cors = $container->get(CorsManagerInterface::class);

$cors->addAllowedOrigin('https://app.example.com');
$cors->addAllowedOrigin('https://staging.example.com');
$cors->addAllowedMethod(['GET', 'POST', 'PUT', 'DELETE']);
$cors->addAllowedHeader(['Content-Type', 'Authorization', 'X-WP-Nonce']);
$cors->setAllowCredentials(true);
$cors->setMaxAge(86400); // Cache preflight 24h
```

**Attention** : le wildcard `*` est interdit quand `allowCredentials` est active. Listez explicitement les origines autorisees.

Le hook `rest_api_init` intercepte les requetes preflight (OPTIONS) a la priorite 5. Le filtre `rest_pre_serve_request` ajoute les en-tetes CORS aux reponses.

---

## Definir une politique de mots de passe

Le `PasswordPolicy` valide les mots de passe lors de la creation et de la mise a jour des utilisateurs.

```php
$security->passwordMinLength(16);
```

### Regles appliquees

- Longueur minimale (configurable, defaut : 12)
- Au moins une majuscule
- Au moins un chiffre
- Au moins un caractere special

Les hooks `user_profile_update_errors` et `registration_errors` sont utilises pour la validation.

---

## Configurer la politique de mises a jour automatiques

Le `AutoUpdatePolicy` controle finement les mises a jour automatiques WordPress.

```php
$autoUpdate = $container->get(AutoUpdatePolicy::class);

$autoUpdate->setMajorCore(false);    // Pas de MAJ majeure automatique
$autoUpdate->setMinorCore(true);     // MAJ mineures (securite) automatiques
$autoUpdate->setPlugins(false);      // Pas de MAJ plugins automatique
$autoUpdate->setThemes(false);       // Pas de MAJ themes automatique
$autoUpdate->setTranslations(true);  // Traductions automatiques

// Exceptions : certains plugins de confiance en MAJ auto
$autoUpdate->setAllowedPlugins(['akismet/akismet.php', 'wordpress-seo/wp-seo.php']);
$autoUpdate->setAllowedThemes(['twentytwentyfour']);
```

---

## Restreindre l'API REST

Le `RestApiSecurity` exige une authentification pour toutes les routes REST sauf les routes publiques.

### Routes publiques par defaut

- `/oembed/*`
- `/wp-site-health/*`

### Ajouter des routes publiques personnalisees

```php
// A l'instanciation via le conteneur DI
$restSecurity = new RestApiSecurity(
    $hookDispatcher,
    $requestContext,
    $userContext,
    $siteContext,
    additionalPublicPatterns: [
        '#^/monplugin/v1/public/#',
        '#^/wc/store/#',
    ]
);
```

### Effet

- Les utilisateurs non authentifies recoivent HTTP 401 sur les routes protegees
- L'index REST (`/wp-json/`) est filtre pour masquer `authentication` et `routes` aux visiteurs non connectes
- Le filtre `rest_authentication_errors` est utilise

---

## Obfusquer l'URL de connexion

Le `AdminUrlObfuscation` remplace `/wp-login.php` par un slug personnalise.

```php
$obfuscation = $container->get(AdminUrlObfuscation::class);
$obfuscation->setLoginSlug('mon-acces-securise');
```

L'acces direct a `/wp-login.php` renvoie une erreur 404. Les filtres `login_url`, `logout_url` et `site_url` sont modifies automatiquement.

---

## Lancer un scan de securite

Utilisez l'endpoint REST pour un scan combine integrite + malware :

```
GET /wp-json/backto/v1/security/scan
```

Reponse :

```json
{
  "file_integrity": {
    "status": "clean|alert",
    "modified": [],
    "missing": [],
    "added": []
  },
  "malware_scan": {
    "status": "clean|alert",
    "suspicious_files": [],
    "scanned_count": 42
  }
}
```

Le scan d'integrite compare les hash SHA-256 des fichiers critiques (`wp-config.php`, `.htaccess`, `wp-settings.php`, etc.) a une baseline. Le scan malware detecte les fichiers PHP dans le repertoire uploads et recherche les patterns dangereux (`eval()`, `base64_decode()`, `shell_exec()`, etc.).
