# Architecture du bundle Security

Ce document explique les choix architecturaux du bundle Security, les patterns utilises et la logique derriere les decisions de conception.

---

## Strategie de defense en profondeur

Le bundle Security applique le principe de **defense en profondeur** (defense-in-depth) : chaque couche de protection fonctionne independamment des autres. Si une couche est contournee, les suivantes restent actives.

Les couches sont organisees ainsi :

```
Requete entrante
  |
  v
[1. En-tetes HTTP]          HttpHeadersHardening, SecurityHeadersConfigurator, CSP
  |
  v
[2. Controle d'acces]       IPAccessControl, RestApiSecurity, DisableXmlRpc
  |
  v
[3. Rate limiting]          RestApiRateLimiter
  |
  v
[4. Authentification]       LoginHardening (throttle) -> 2FA -> SessionManager
  |
  v
[5. Autorisation]           CapabilityHardening, DisableFileEditor
  |
  v
[6. Validation des donnees] DatabaseHardening, UploadSecurity, CommentSpamProtection
  |
  v
[7. Surveillance]           SecurityAuditLogger, LoginAnomalyDetector, FileIntegrityMonitor
  |
  v
[8. Notification]           SecurityNotifier
```

Chaque couche agit sur un aspect specifique de la securite. Par exemple, meme si un attaquant reussit a contourner le rate limiting (couche 3), il devra encore passer le throttle de login (couche 4), la 2FA, puis le durcissement des capabilities (couche 5), pendant que la couche 7 enregistre chaque tentative.

---

## Le pattern SecurityRuleInterface

### Probleme resolu

Comment ajouter de nouvelles regles de securite sans modifier le code existant, tout en garantissant que toutes les regles sont activees au demarrage ?

### Solution

Le pattern repose sur trois elements :

1. **`SecurityRuleInterface`** etend `HookInterface` et ajoute `getName(): string`. C'est un marqueur qui identifie une classe comme regle de securite.

2. **Auto-configuration DI** : dans `SecurityExtension::register()`, toute classe implementant `SecurityRuleInterface` recoit automatiquement le tag `wordpress.security_rule` :

```php
$containerBuilder->registerForAutoconfiguration(SecurityRuleInterface::class)
    ->addTag('wordpress.security_rule');
```

3. **`RegisterSecurityRulePass`** : ce compiler pass collecte tous les services tagges et les injecte dans le `SecurityRuleRegistry`. Il etend `AbstractTaggedServiceCompilerPass`, ce qui standardise le pattern de collecte de services tagges dans le framework.

### Avantages

- **Open/Closed** : ajouter une regle = creer une classe, pas de configuration supplementaire
- **Decouplage** : chaque regle ne connait que ses propres dependances
- **Testabilite** : chaque regle est testable en isolation
- **Visibilite** : le `SecurityRuleRegistry` permet d'inspecter les regles actives a tout moment (utilise par `SecurityHealthCheck`)

---

## Ordonnancement des priorites de hooks

Les priorites des hooks WordPress sont critiques pour le bon fonctionnement de la securite. Le bundle definit un ordre explicite sur les hooks partages :

### Chaine `authenticate`

```
Priorite 20 : WordPress core (authentification username/password)
Priorite 30 : LoginHardening (throttling IP et compte)
Priorite 40 : TwoFactorAuthentication (verification TOTP/backup code)
```

Cette chaine garantit que :
- Le throttle bloque les tentatives avant la 2FA (economie de ressources)
- La 2FA ne s'execute que sur les authentifications reussies
- Les erreurs de throttle sont renvoyees avant toute verification de code

### Chaine `wp_login`

```
Priorite 10 : LoginHardening (reset des compteurs)
Priorite 10 : SecurityAuditLogger (journalisation)
Priorite 20 : LoginAnomalyDetector (detection d'anomalies apres enregistrement)
```

### Chaine `set_user_role`

```
Priorite  5 : CapabilityHardening (bloque auto-promotion, revert le role)
Priorite 10 : SecurityAuditLogger (journalise le changement)
Priorite 15 : SecurityNotifier (envoie une notification email)
```

L'ordre est documente dans les PHPDoc de chaque classe. Cette convention evite les conflits et rend le flux previsible.

---

## Design du journal d'audit (Audit Trail)

### Architecture

Le journal d'audit suit le pattern **Repository** :

```
SecurityAuditLogger (collecte)
    |
    v
AuditLogRepositoryInterface (port)
    |
    v
WordPressAuditLogRepository (adaptateur, stockage wp_options / table custom)
```

### Choix de conception

**Severite a trois niveaux** : l'enum `AuditLogSeverity` definit `Info`, `Warning` et `Critical`. Trois niveaux suffisent pour la securite WordPress — un systeme de logging general (comme PSR-3) serait surdimensionne.

**Options critiques filtrees** : le `SecurityAuditLogger` ne journalise que les modifications sur un ensemble restreint d'options (`siteurl`, `home`, `admin_email`, `users_can_register`, `default_role`, `permalink_structure`, `blogdescription`). Cela evite le bruit des mises a jour de transients et d'options non significatives.

**Sanitization des valeurs** : les valeurs complexes (tableaux, objets) sont remplacees par `[complex value]` pour eviter les problemes de serialisation et les fuites de donnees sensibles.

**Export avec cooldown** : `AuditLogCsvExporter` impose un delai de 60 secondes entre les exports pour empecher les abus (DoS par exports repetes).

### Chaine de notification

```
Evenement securite
    |
    v
SecurityAuditLogger (stocke dans le repository)
    |
    v
Action WordPress 'backto_security_event'
    |
    v
SecurityNotifier (filtre les evenements critiques, envoie les emails)
    |
    v
SecurityAlertFormatter (formate sujet et corps)
    |
    v
MailerInterface -> WordPressMailer (wp_mail)
```

---

## Flux d'authentification 2FA

### Diagramme de sequence

```
Utilisateur          WordPress Core       LoginHardening       TwoFactorAuth
    |                     |                     |                    |
    |-- login request --->|                     |                    |
    |                     |-- authenticate(20)->|                    |
    |                     |    (user valide)    |                    |
    |                     |                     |-- throttle(30) --->|
    |                     |                     |    (IP OK)         |
    |                     |                     |                    |-- check 2FA(40)
    |                     |                     |                    |    user a 2FA?
    |                     |                     |                    |
    |<--- WP_Error('two_factor_required') ------|--------------------+
    |                     |                     |                    |
    |-- login + code 2FA->|                     |                    |
    |                     |-- authenticate(20)->|                    |
    |                     |                     |-- throttle(30) --->|
    |                     |                     |                    |-- verify code(40)
    |                     |                     |                    |    TOTP OK?
    |                     |                     |                    |    backup code?
    |<--- session valide --|--------------------|--------------------|
```

### Points cles

- La 2FA n'ajoute pas de page intermediaire : elle utilise le filtre `authenticate` a la priorite 40 pour renvoyer un `WP_Error` avec le code `two_factor_required` qui signale au formulaire de login d'afficher le champ 2FA.
- Le champ POST est `backto_2fa_code`, sanitize pour ne garder que les caracteres alphanumeriques.
- Les codes de secours sont verifies en temps constant (iteration de tous les hashes) pour prevenir les attaques par timing.
- La consommation d'un code de secours est atomique : le code est supprime immediatement apres verification, et le nombre restant est journalise.

### Separation des responsabilites

- `TwoFactorAuthentication` : uniquement l'interception de login (SRP)
- `TwoFactorSetupManager` : cycle de vie (setup, confirm, disable, regenerate)
- `TotpProvider` : algorithme TOTP pur (RFC 6238)
- `BackupCodeManager` : generation et verification des codes de secours
- `Base32` : encodage/decodage pour les secrets TOTP

---

## Pattern hexagonal dans le bundle Security

Le bundle Security applique l'**architecture hexagonale** (ports et adaptateurs) de maniere systematique.

### Ports (interfaces dans `Contracts/`)

Les ports definissent les capacites dont la logique metier a besoin :

| Port | Responsabilite |
|------|----------------|
| `LoginThrottleInterface` | Comptage et verrouillage des tentatives |
| `AuditLogRepositoryInterface` | Stockage/lecture des evenements d'audit |
| `FileIntegrityRepositoryInterface` | Stockage/lecture des baselines de hash |
| `RateLimiterRepositoryInterface` | Comptage des requetes API |
| `LoginLocationRepositoryInterface` | Historique des localisations de connexion |
| `TwoFactorRepositoryInterface` | Stockage des secrets et codes 2FA |
| `ClientIpResolverInterface` | Resolution de l'IP client |
| `NonceManagerInterface` | Generation/verification des nonces |
| `InputSanitizerInterface` | Sanitization des entrees |
| `OutputEscaperInterface` | Echappement des sorties |
| `MailerInterface` | Envoi d'emails |
| `ContentSecurityPolicyInterface` | Gestion des directives CSP |
| `CorsManagerInterface` | Gestion CORS |
| `SubresourceIntegrityInterface` | Gestion SRI |
| `IPAccessControlInterface` | Controle d'acces IP |
| `SecurityNotifierInterface` | Notifications de securite |

### Adaptateurs (dans `Infrastructure/`)

Les adaptateurs implementent les ports avec les specifites WordPress :

| Adaptateur | Port | Mecanisme WordPress |
|------------|------|---------------------|
| `WordPressLoginThrottle` | `LoginThrottleInterface` | transients WP |
| `WordPressAuditLogRepository` | `AuditLogRepositoryInterface` | table custom / wp_options |
| `WordPressFileIntegrityRepository` | `FileIntegrityRepositoryInterface` | wp_options |
| `WordPressRateLimiterRepository` | `RateLimiterRepositoryInterface` | transients WP |
| `WordPressLoginLocationRepository` | `LoginLocationRepositoryInterface` | user meta |
| `WordPressTwoFactorRepository` | `TwoFactorRepositoryInterface` | user meta |
| `WordPressNonceManager` | `NonceManagerInterface` | wp_create_nonce / wp_verify_nonce |
| `WordPressInputSanitizer` | `InputSanitizerInterface` | sanitize_text_field et al. |
| `WordPressOutputEscaper` | `OutputEscaperInterface` | esc_html, esc_attr et al. |
| `WordPressMailer` | `MailerInterface` | wp_mail |

### Enregistrement dans `SecurityExtension`

La methode `registerPortBindings()` connecte chaque port a son adaptateur WordPress via le conteneur DI :

```php
$containerBuilder->register(LoginThrottleInterface::class, WordPressLoginThrottle::class);
$containerBuilder->setAlias(WordPressLoginThrottle::class, LoginThrottleInterface::class);
```

Des alias supplementaires sont definis quand une interface a plusieurs facettes. Par exemple, `TwoFactorRepositoryInterface` a trois alias (`TwoFactorStateInterface`, `TwoFactorSecretInterface`, `TwoFactorBackupCodeInterface`) qui permettent de typer precisement les dependances dans les constructeurs tout en utilisant une seule implementation.

### Avantages

- **Testabilite** : les regles de securite ne dependent jamais de WordPress directement, uniquement des ports. Les tests unitaires injectent des mocks.
- **Substituabilite** : changer de stockage (ex: Redis au lieu de transients) = creer un nouvel adaptateur, pas de modification de la logique metier.
- **Separation des couches** : la logique de securite (`LoginHardening`, `TwoFactorAuthentication`, etc.) ne contient aucun appel WordPress direct. Tout passe par les interfaces.

---

## Remarques supplementaires

### Le trait `HtmlEscapeTrait`

Utilise par `CommentSpamProtection` pour echapper les sorties HTML. Centralise `escapeHtml()` et `escapeAttr()` pour les classes qui produisent du HTML sans passer par un moteur de templates.

### Le health check comme invariant

`SecurityHealthCheck` agit comme un invariant de deploiement : il verifie que les 8 regles critiques sont presentes dans le registre. Un deploiement ou les regles critiques manquent retourne un statut `unhealthy`. C'est un mecanisme de detection rapide des mauvaises configurations.

### Gestion des sessions concurrentes

`SessionManager` detruit les sessions les plus anciennes quand le maximum est depasse. Le mecanisme inclut deux passes pour gerer les conditions de course (nouvelle session creee entre la lecture et la suppression).
