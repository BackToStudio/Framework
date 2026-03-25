# ServerConfigGenerator

`BackTo\Framework\Bundle\Security\Network\ServerConfigGenerator`

Genere des fichiers de configuration Nginx et Apache pour bloquer le trafic de bots au niveau du serveur web, avant l'invocation de PHP.

## Injection DI

Le service est enregistre automatiquement par le Security Bundle. Ses setters sont appeles par le `ConfigureServerConfigGeneratorPass` a partir des parametres `security.bot_protection.*`.

## Methodes publiques

### Configuration

| Methode | Description |
|---|---|
| `setBlockedUserAgents(string[])` | Remplace la liste des User-Agents bloques |
| `addBlockedUserAgents(string[])` | Ajoute des User-Agents a la liste existante |
| `setBlockedIps(string[])` | Definit les IP/CIDR a bloquer |
| `setSensitiveEndpoints(string[])` | Definit les URI avec rate limiting renforce |
| `setGlobalRateLimit(int $rps, int $burst)` | Limite globale en requetes/seconde + burst |
| `setSensitiveRateLimit(int $rps, int $burst)` | Limite renforcee pour les endpoints sensibles |
| `setMaxConnectionsPerIp(int)` | Connexions simultanees max par IP |
| `setBlockEmptyUserAgent(bool)` | Bloquer les requetes sans User-Agent |

Tous les setters retournent `$this` (fluent interface).

### Generation

| Methode | Description |
|---|---|
| `generateNginx(): string` | Retourne la configuration Nginx complete |
| `generateApache(): string` | Retourne la configuration Apache complete |
| `writeNginx(string $path): bool` | Ecrit la configuration Nginx dans un fichier |
| `writeApache(string $path): bool` | Ecrit la configuration Apache dans un fichier |
| `getConfiguration(): array` | Retourne la configuration courante |

### `getConfiguration()` — Format de retour

```php
[
    'blocked_user_agents'    => string[],
    'blocked_ips'            => string[],
    'sensitive_endpoints'    => string[],
    'global_rate_limit'      => int,
    'global_burst'           => int,
    'sensitive_rate_limit'   => int,
    'sensitive_burst'        => int,
    'max_connections_per_ip' => int,
    'block_empty_user_agent' => bool,
]
```

## Sortie generee — Nginx

Le fichier genere contient deux sections :

**Section 1 — bloc `http {}`** (zones de rate limiting) :

```nginx
limit_req_zone $binary_remote_addr zone=backto_global:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=backto_sensitive:10m rate=2r/s;
limit_conn_zone $binary_remote_addr zone=backto_conn:10m;
```

**Section 2 — bloc `server {}`** :

- Blocage User-Agent (`if` + `return 403`)
- Blocage User-Agent vide
- `deny` des IP bloquees
- `location /` avec `limit_req` et `limit_conn`
- `location ~` pour les endpoints sensibles

## Sortie generee — Apache

Le fichier genere contient :

- `mod_rewrite` : blocage par User-Agent (`RewriteCond` + `RewriteRule`)
- `mod_authz_core` : blocage IP (`Require not ip`)
- `mod_evasive24` : rate limiting (`DOSPageCount`, `DOSSiteCount`, etc.)

## Compiler Pass

`ConfigureServerConfigGeneratorPass` lit les parametres du conteneur et appelle les setters :

| Parametre DI | Methode appelee |
|---|---|
| `security.bot_protection.blocked_user_agents` | `setBlockedUserAgents()` |
| `security.bot_protection.blocked_ips` | `setBlockedIps()` |
| `security.bot_protection.sensitive_endpoints` | `setSensitiveEndpoints()` |
| `security.bot_protection.global_rate_limit` + `global_burst` | `setGlobalRateLimit()` |
| `security.bot_protection.sensitive_rate_limit` + `sensitive_burst` | `setSensitiveRateLimit()` |
| `security.bot_protection.max_connections_per_ip` | `setMaxConnectionsPerIp()` |
| `security.bot_protection.block_empty_user_agent` | `setBlockEmptyUserAgent()` |

## Commande WP-CLI

```
wp backto:generate-server-config [--server=<nginx|apache|both>] [--output=<stdout|file>] [--dir=<path>] [--blocked-ips=<ips>] [--extra-bots=<bots>]
```

| Option | Description | Defaut |
|---|---|---|
| `--server` | Type de serveur web | `nginx` |
| `--output` | Mode de sortie | `stdout` |
| `--dir` | Repertoire de sortie (mode `file`) | `.` |
| `--blocked-ips` | IP/CIDR supplementaires (separes par virgule) | — |
| `--extra-bots` | User-Agents supplementaires (separes par virgule) | — |

Fichiers generes :
- Nginx : `backto-bot-protection.conf`
- Apache : `.htaccess-bot-protection`
