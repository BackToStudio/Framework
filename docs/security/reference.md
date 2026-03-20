# Reference du bundle Security

Reference complete de toutes les classes du bundle Security, groupees par domaine fonctionnel.

**Namespace de base :** `BackTo\Framework\Bundle\Security`

---

## Login

### `LoginHardening`

Durcit le flux de connexion WordPress avec throttling et messages generiques.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `authenticate` | Filter | 30 | Verifie le throttle IP/compte avant l'authentification |
| `login_errors` | Filter | 10 | Remplace les messages d'erreur par un message generique |
| `wp_login_failed` | Action | 10 | Enregistre les tentatives echouees (IP + compte) |
| `wp_login` | Action | 10 | Reinitialise les compteurs apres un login reussi |

**Dependances :** `LoginThrottleInterface`, `ClientIpResolverInterface`, `LoggerInterface`

### `LoginAnomalyDetector`

Detecte les anomalies de connexion : nouveau pays, voyage impossible (connexion depuis deux pays en moins d'une heure).

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `wp_login` | Action | 20 | Analyse la localisation apres enregistrement du login |

**Seuil de voyage impossible :** 3600 secondes (1 heure).
**Resolution du pays :** en-tetes CDN (`HTTP_CF_IPCOUNTRY`, `HTTP_X_COUNTRY_CODE`) via proxies de confiance, sinon `unknown`.
**Dependances :** `LoginLocationRepositoryInterface`, `ClientIpResolverInterface`

### `AdminUrlObfuscation`

Masque `/wp-login.php` derriere un slug personnalise.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `init` | Action | 10 | Intercepte les requetes vers le slug personnalise |
| `wp_loaded` | Action | 10 | Bloque l'acces direct a `wp-login.php` (404) |
| `login_url` | Filter | 10 | Reecrit l'URL de connexion |
| `logout_url` | Filter | 10 | Reecrit l'URL de deconnexion |
| `site_url` | Filter | 10 | Reecrit les URLs contenant `wp-login.php` |

**Methode cle :** `setLoginSlug(string $slug)`

### `DisableUserEnumeration`

Empeche l'enumeration des utilisateurs.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `init` | Action | 10 | Redirige les requetes `?author=N` vers l'accueil (301) |
| `rest_endpoints` | Filter | 10 | Supprime `/wp/v2/users` pour les non-authentifies |

### `SessionManager`

Controle les sessions concurrentes par utilisateur.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `attach_session_information` | Filter | 10 | Ajoute IP, user-agent et timestamp aux sessions |
| `wp_login` | Action | 10 | Detruit les sessions excedentaires (les plus anciennes) |
| `session_token_manager` | Filter | 10 | Retourne la classe WP_User_Meta_Session_Tokens |

**Option :** `security.max_concurrent_sessions` (defaut : 1)

### `PasswordPolicy`

Valide les mots de passe selon une politique configurable.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `user_profile_update_errors` | Action | 10 | Valide le mot de passe a la mise a jour du profil |
| `registration_errors` | Action | 10 | Valide le mot de passe a l'inscription |

**Options du constructeur :**

| Parametre | Defaut | Description |
|-----------|--------|-------------|
| `$minLength` | 12 | Longueur minimale |
| `$requireUppercase` | `true` | Majuscule requise |
| `$requireNumber` | `true` | Chiffre requis |
| `$requireSpecialChar` | `true` | Caractere special requis |

---

## 2FA (Two-Factor Authentication)

Namespace : `BackTo\Framework\Bundle\Security\TwoFactor`

### `TwoFactorAuthentication`

Intercepteur de connexion pour la verification 2FA.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `authenticate` | Filter | 40 | Exige un code TOTP ou code de secours apres l'auth WordPress |

**Flux :** auth WordPress (20) -> throttle LoginHardening (30) -> 2FA (40).
Le champ POST attendu est `backto_2fa_code`. Les codes sont sanitizes (seuls les caracteres alphanumeriques sont conserves).

### `TwoFactorSetupManager`

Gere le cycle de vie 2FA : configuration, confirmation, desactivation, regeneration des codes de secours.

**Methodes :**

| Methode | Description |
|---------|-------------|
| `setup(int $userId, string $accountName)` | Genere secret + URI provisioning + 8 codes de secours |
| `confirmSetup(int $userId, string $code)` | Verifie le code et active la 2FA |
| `disableForUser(int $userId)` | Desactive la 2FA et supprime toutes les donnees |
| `regenerateBackupCodes(int $userId)` | Genere 8 nouveaux codes de secours |
| `isEnabledForUser(int $userId)` | Verifie si la 2FA est active |

### `TotpProvider`

Implementation TOTP conforme RFC 6238 / RFC 4226.

| Parametre | Valeur |
|-----------|--------|
| Periode | 30 secondes |
| Digits | 6 |
| Algorithme | HMAC-SHA1 |
| Tolerance | +/- 1 fenetre |

**Methodes :**

| Methode | Description |
|---------|-------------|
| `generateSecret(int $length = 20)` | Genere un secret Base32 |
| `generateCode(string $secret, ?int $timestamp)` | Genere un code TOTP |
| `verifyCode(string $secret, string $code, int $discrepancy = 1)` | Verifie un code avec tolerance |
| `getProvisioningUri(string $secret, string $accountName, string $issuer)` | URI `otpauth://totp/...` |

### `BackupCodeManager`

Genere et verifie les codes de secours (8 codes de 8 chiffres, format `XXXX-XXXX`).

- Hashage : `bcrypt` (via `password_hash`)
- Verification : iteration de tous les codes en temps constant (anti timing attack)
- Methode `findMatchingIndex()` retourne l'index du code consomme

### `Base32`

Encodeur/decodeur Base32 (RFC 4648) pour les secrets TOTP.

---

## En-tetes HTTP (Headers)

### `HttpHeadersHardening`

Envoie les en-tetes de securite de base.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `send_headers` | Action | 10 | Envoie les en-tetes sur les pages |
| `rest_api_init` | Action | 10 | Envoie les en-tetes sur les requetes REST |

**En-tetes envoyes :**
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`
- Suppression de `X-Powered-By`

### `SecurityHeadersConfigurator`

Configuration avancee des en-tetes par environnement (production/staging/development).

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `send_headers` | Action | 10 | Envoie les en-tetes configures |
| `rest_api_init` | Action | 10 | Idem pour REST |

**Presets par environnement :**

| Environnement | HSTS | Permissions-Policy |
|---------------|------|--------------------|
| `production` | `max-age=31536000; includeSubDomains; preload` | `camera=(), microphone=(), geolocation=(), payment=()` |
| `staging` | `max-age=86400` | `camera=(), microphone=(), geolocation=()` |
| `development` | _(absent)_ | _(absent)_ |

**Methodes de configuration :** `setHstsMaxAge()`, `setFrameOptions()`, `setReferrerPolicy()`, `setPermissionsPolicy()`, `setHeader()`, `removeHeader()`

### `ContentSecurityPolicyManager`

Gere l'en-tete CSP avec generation automatique de nonces.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `send_headers` | Action | 10 | Envoie l'en-tete CSP |
| `script_loader_tag` | Filter | 10 | Ajoute `nonce="..."` aux balises `<script>` |

**Option :** `security.csp_report_only` (defaut : `false`)
**Methode cle :** `addDirective(string $directive, string|array $value)`

### `SubresourceIntegrity`

Ajoute les attributs `integrity` et `crossorigin` aux scripts et styles externes (protection supply chain CDN).

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `script_loader_tag` | Filter | 20 | Ajoute `integrity="sha..."` aux scripts |
| `style_loader_tag` | Filter | 20 | Ajoute `integrity="sha..."` aux styles |

**Methode :** `registerHash(string $handle, string $hash)` — format attendu : `sha256-...`, `sha384-...` ou `sha512-...`

### `CookieHardening`

Force les attributs de securite sur les cookies d'authentification WordPress.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `secure_auth_cookie` | Filter | 10 | Force le flag Secure |
| `secure_logged_in_cookie` | Filter | 10 | Force le flag Secure |
| `set_auth_cookie` | Action | 10 | Re-set le cookie auth avec attributs durcis |
| `set_logged_in_cookie` | Action | 10 | Re-set le cookie logged_in avec attributs durcis |
| `init` | Action | 10 | Configure les parametres du cookie de session |

**Attributs par defaut :** `Secure=true`, `HttpOnly=true`, `SameSite=Lax`

### `HideWordPressVersion`

Supprime les indicateurs de version WordPress.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `the_generator` | Filter | 10 | Retourne une chaine vide |
| `style_loader_src` | Filter | 10 | Supprime le parametre `ver=` des URLs CSS |
| `script_loader_src` | Filter | 10 | Supprime le parametre `ver=` des URLs JS |
| `wp_head` | Action | 1 | Supprime `wp_generator` du `<head>` |

---

## Audit

### `SecurityAuditLogger`

Journalise les evenements de securite dans un stockage persistant.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `wp_login` | Action | 10 | Enregistre les connexions reussies |
| `wp_login_failed` | Action | 10 | Enregistre les echecs de connexion |
| `set_user_role` | Action | 10 | Enregistre les changements de role |
| `updated_option` | Action | 10 | Enregistre les modifications d'options critiques |
| `activated_plugin` | Action | 10 | Enregistre l'activation de plugin |
| `deactivated_plugin` | Action | 10 | Enregistre la desactivation de plugin |
| `switch_theme` | Action | 10 | Enregistre le changement de theme |
| `user_register` | Action | 10 | Enregistre la creation d'utilisateur |
| `delete_user` | Action | 10 | Enregistre la suppression d'utilisateur |

**Options critiques surveillees :** `siteurl`, `home`, `admin_email`, `users_can_register`, `default_role`, `permalink_structure`, `blogdescription`

**Niveaux de severite :** `AuditLogSeverity::Info`, `AuditLogSeverity::Warning`, `AuditLogSeverity::Critical`

### `SecurityNotifier`

Envoie des notifications email sur les evenements critiques.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `backto_security_event` | Action | 10 | Reagit aux evenements dispatches |
| `set_user_role` | Action | 15 | Notifie les promotions admin |
| `wp_login_failed` | Action | 10 | Compte les echecs et notifie au seuil |

**Seuil d'echecs de connexion :** 10 en 10 minutes (configurable via `setFailedLoginThreshold()`)

### `SecurityAlertFormatter`

Formate les sujets et corps des emails d'alerte. Format : `[SEVERITY] SiteName - Event label`.

### `AuditLogAdminPage`

Page d'administration pour visualiser le journal d'audit. Menu slug : `backto-audit-log`. Capability requise : `manage_options`. Supporte le filtrage, l'export CSV et la purge.

### `AuditLogCsvExporter`

Exporte les evenements d'audit en CSV. Limite : 10 000 evenements. Cooldown : 60 secondes entre exports.

### `AuditLog\AuditLogRenderer`

Rendu HTML de la page d'audit dans l'admin WordPress.

---

## Fichiers (Files)

### `FileIntegrityMonitor`

Surveille les fichiers critiques WordPress via des hashes SHA-256.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `admin_init` | Action | 10 | Planifie le cron si necessaire |
| `backto_file_integrity_check` | Action | 10 | Execute la verification horaire |

**Fichiers surveilles :** `wp-config.php`, `.htaccess`, `wp-settings.php`, `wp-login.php`, `wp-load.php`, `wp-blog-header.php`, `index.php`, `wp-cron.php`, `xmlrpc.php`

**Cron :** hook `backto_file_integrity_check`, frequence `hourly`

### `MalwareScanner`

Scanne le repertoire uploads pour les fichiers PHP suspects et les patterns de code malveillant.

**Extensions scannees :** `php`, `php3`, `php4`, `php5`, `phtml`, `phar`

**Patterns detectes :** `eval()`, `base64_decode()`, `shell_exec()`, `exec()`, `system()`, `passthru()`, `proc_open()`, `popen()`, `pcntl_exec()`, `assert()`, superglobales, `curl_exec()`, `file_get_contents($...)`, `preg_replace` avec flag `e`, sequences hexadecimales, `chr()`

**Profondeur max de scan :** 20 niveaux. Les symlinks sont ignores.

### `DirectoryProtection`

Protege le repertoire uploads avec `.htaccess` (desactive le listing et bloque les fichiers PHP) et `index.php`.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `admin_init` | Action | 10 | Verifie la presence des fichiers de protection |

Implemente aussi `ActivationHooks` pour creer les fichiers a l'activation du plugin.

### `UploadSecurity`

Valide les fichiers telecharges : extensions dangereuses, doubles extensions, magic bytes, sanitization SVG.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `upload_mimes` | Filter | 10 | Supprime les types MIME dangereux |
| `wp_handle_upload_prefilter` | Filter | 10 | Valide chaque fichier avant upload |

**Verifications SVG :** balises dangereuses (`<script>`, `<foreignObject>`, etc.), attributs d'evenement (`on*=`), URIs `data:` / `javascript:` y compris dans les sections CDATA.

---

## API

### `RestApiSecurity`

Exige l'authentification pour l'API REST.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `rest_authentication_errors` | Filter | 10 | Retourne 401 pour les non-authentifies |
| `rest_index` | Filter | 10 | Masque `authentication` et `routes` de l'index REST |

**Routes publiques :** `/oembed/*`, `/wp-site-health/*` + patterns personnalises.

### `RestApiRateLimiter`

Rate limiting par IP et par route sur l'API REST.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `rest_pre_dispatch` | Filter | 10 | Verifie et incremente le compteur de requetes |

**Defauts :** 60 requetes / 60 secondes. Configurable par route.

### `CorsManager`

Gestion fine des en-tetes CORS.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `rest_api_init` | Action | 5 | Intercepte les preflight OPTIONS |
| `rest_pre_serve_request` | Filter | 10 | Ajoute les en-tetes CORS aux reponses |

**Methodes de configuration :** `addAllowedOrigin()`, `addAllowedMethod()`, `addAllowedHeader()`, `setAllowCredentials()`, `setMaxAge()`

**Protection :** rejet des origines contenant CRLF (prevention injection d'en-tetes).

### Routes REST

| Route | Methode | Description | Capability |
|-------|---------|-------------|------------|
| `/backto/v1/security/audit-log` | GET | Journal d'audit avec filtres | `manage_options` |
| `/backto/v1/security/health` | GET | Statut du health check securite | `manage_options` |
| `/backto/v1/security/scan` | GET | Scan integrite + malware | `manage_options` |

---

## Configuration (Config)

### `SecurityConfiguration`

Definit les valeurs par defaut des parametres du bundle. Applique les defauts au `ContainerBuilder` si le parametre n'est pas deja defini.

### `SecurityConfigurator`

API fluent pour configurer le bundle depuis `config/security.php`.

**Methodes disponibles :**

| Methode | Parametre DI |
|---------|--------------|
| `headersEnabled(bool)` | `security.headers_enabled` |
| `xmlrpcDisabled(bool)` | `security.xmlrpc_disabled` |
| `hideVersion(bool)` | `security.hide_version` |
| `cspReportOnly(bool)` | `security.csp_report_only` |
| `passwordMinLength(int)` | `security.password_min_length` |
| `maxConcurrentSessions(int)` | `security.max_concurrent_sessions` |
| `restApiRequireAuth(bool)` | `security.rest_api_require_auth` |
| `disableFileEditor(bool)` | `security.disable_file_editor` |
| `twoFactorEnabled(bool)` | `security.two_factor_enabled` |
| `twoFactorIssuer(string)` | `security.two_factor_issuer` |

### `SecurityExtension`

Extension DI qui enregistre le bundle Security. Configure l'auto-decouverte des `SecurityRuleInterface` via le tag `wordpress.security_rule` et le `RegisterSecurityRulePass`.

### `SecurityRuleRegistry`

Registre de toutes les regles de securite actives. Methodes : `add()`, `getRules()`, `has()`, `count()`, `getActiveRuleNames()`.

### `RegisterSecurityRulePass`

Compiler pass qui collecte tous les services tagges `wordpress.security_rule` et les injecte dans le `SecurityRuleRegistry`.

---

## Systeme

### `DisableXmlRpc`

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `xmlrpc_enabled` | Filter | 10 | Retourne `false` |
| `wp_headers` | Filter | 10 | Supprime l'en-tete `X-Pingback` |
| `wp` | Action | 10 | Supprime les liens RSD et WLW |

### `DisableFileEditor`

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `init` | Action | 10 | Definit `DISALLOW_FILE_EDIT = true` |

### `DisablePublicCron`

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `init` | Action | 1 | Definit `DISABLE_WP_CRON = true` |

### `AutoUpdatePolicy`

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `allow_major_auto_core_updates` | Filter | 10 | Controle MAJ majeures |
| `allow_minor_auto_core_updates` | Filter | 10 | Controle MAJ mineures |
| `auto_update_plugin` | Filter | 10 | Controle MAJ plugins (avec exceptions) |
| `auto_update_theme` | Filter | 10 | Controle MAJ themes (avec exceptions) |
| `auto_update_translation` | Filter | 10 | Controle MAJ traductions |

### `CommentSpamProtection`

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `comment_form` | Action | 10 | Affiche le champ honeypot |
| `preprocess_comment` | Filter | 10 | Valide le commentaire (honeypot, referer, liens, patterns) |

**Seuil de liens :** 2 par defaut. Patterns detectes : `[url]`, `<a href`, `on*=`, `<script>`, `<iframe>`, `<object>`, `<embed>`, `<form>`.

### `CapabilityHardening`

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `set_user_role` | Action | 5 | Bloque l'auto-promotion admin, journalise |
| `user_has_cap` | Filter | 10 | Restreint `promote_users` aux admins |

### `DatabaseHardening`

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `query` | Filter | 10 | Detecte les patterns SQL dangereux et les requetes non preparees |

**Patterns detectes :** `DROP TABLE`, `TRUNCATE TABLE`, `ALTER TABLE`, `LOAD_FILE()`, `INTO OUTFILE/DUMPFILE`, `UNION SELECT`, `SLEEP()`, `BENCHMARK()`

### `PhpConfigHardening`

Audite la configuration PHP (lecture seule, ne modifie pas `php.ini`).

**Directives verifiees :** `expose_php`, `display_errors`, `display_startup_errors`, `allow_url_fopen`, `allow_url_include`, `session.cookie_httponly`, `session.cookie_secure`, `session.use_strict_mode`, `disable_functions`, `open_basedir`

### `ClientIpResolver`

Resout l'IP reelle du client via les en-tetes de proxies de confiance : `HTTP_CF_CONNECTING_IP`, `HTTP_X_FORWARDED_FOR`, `HTTP_X_REAL_IP`.

### `IPAccessControl`

Controle d'acces IP avec support CIDR IPv4/IPv6.

| Hook | Type | Priorite | Description |
|------|------|----------|-------------|
| `admin_init` | Action | 10 | Verifie l'acces admin |
| `login_init` | Action | 10 | Verifie l'acces login |

### `SecurityHealthCheck`

Health check qui verifie les regles critiques actives, l'editeur de fichiers et la version PHP (>= 8.2).

**Regles critiques :** `http_headers_hardening`, `disable_xmlrpc`, `hide_wordpress_version`, `login_hardening`, `upload_security`, `capability_hardening`, `security_audit_logger`, `disable_file_editor`

---

## Contrats (Contracts)

| Interface | Implementation par defaut |
|-----------|--------------------------|
| `SecurityRuleInterface` | Toutes les regles de securite |
| `ContentSecurityPolicyInterface` | `ContentSecurityPolicyManager` |
| `CorsManagerInterface` | `CorsManager` |
| `IPAccessControlInterface` | `IPAccessControl` |
| `SubresourceIntegrityInterface` | `SubresourceIntegrity` |
| `LoginThrottleInterface` | `WordPressLoginThrottle` |
| `IpLoginThrottleInterface` | alias de `LoginThrottleInterface` |
| `AccountLoginThrottleInterface` | alias de `LoginThrottleInterface` |
| `AuditLogRepositoryInterface` | `WordPressAuditLogRepository` |
| `FileIntegrityRepositoryInterface` | `WordPressFileIntegrityRepository` |
| `LoginLocationRepositoryInterface` | `WordPressLoginLocationRepository` |
| `RateLimiterRepositoryInterface` | `WordPressRateLimiterRepository` |
| `ClientIpResolverInterface` | `ClientIpResolver` |
| `NonceManagerInterface` | `WordPressNonceManager` |
| `InputSanitizerInterface` | `WordPressInputSanitizer` |
| `OutputEscaperInterface` | `WordPressOutputEscaper` |
| `MailerInterface` | `WordPressMailer` |
| `SecurityNotifierInterface` | `SecurityNotifier` |
| `TotpProviderInterface` | `TotpProvider` |
| `TwoFactorRepositoryInterface` | `WordPressTwoFactorRepository` |
| `TwoFactorStateInterface` | alias de `TwoFactorRepositoryInterface` |
| `TwoFactorSecretInterface` | alias de `TwoFactorRepositoryInterface` |
| `TwoFactorBackupCodeInterface` | alias de `TwoFactorRepositoryInterface` |
| `BackupCodeManagerInterface` | `BackupCodeManager` |
| `AuditLogSeverity` | Enum : `Info`, `Warning`, `Critical` |
