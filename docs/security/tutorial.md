# Tutoriel : Demarrer avec le bundle Security

Ce tutoriel vous guide pas a pas pour activer la securite dans un plugin BackTo Framework, configurer le durcissement de base et activer l'authentification a deux facteurs.

## Prerequis

- PHP 8.2+
- Un plugin BackTo Framework fonctionnel
- Acces administrateur a WordPress

## Etape 1 : Enregistrer le bundle Security

Le bundle Security se charge via `SecurityExtension`. Enregistrez-le dans votre plugin :

```php
use BackTo\Framework\Bundle\Security\SecurityExtension;

final class MyPlugin extends AbstractPlugin
{
    protected function getExtensions(): array
    {
        return [
            new SecurityExtension(),
        ];
    }
}
```

A ce stade, toutes les regles de securite sont automatiquement decouvertes et enregistrees via le tag DI `wordpress.security_rule`. Les valeurs par defaut sont appliquees.

## Etape 2 : Comprendre les valeurs par defaut

Le bundle est actif des l'enregistrement avec ces valeurs par defaut :

| Parametre | Valeur par defaut | Effet |
|-----------|-------------------|-------|
| `security.headers_enabled` | `true` | En-tetes HTTP securises actifs |
| `security.xmlrpc_disabled` | `true` | XML-RPC desactive |
| `security.hide_version` | `true` | Version WordPress masquee |
| `security.csp_report_only` | `false` | CSP en mode enforcement |
| `security.password_min_length` | `12` | Longueur minimale des mots de passe |
| `security.max_concurrent_sessions` | `1` | Une seule session par utilisateur |
| `security.rest_api_require_auth` | `true` | REST API necessite authentification |
| `security.disable_file_editor` | `true` | Editeur de fichiers desactive |
| `security.two_factor_enabled` | `false` | 2FA desactive par defaut |
| `security.two_factor_issuer` | `'WordPress'` | Nom affiche dans l'app TOTP |

## Etape 3 : Configurer le durcissement de base

Creez un fichier `config/security.php` dans votre plugin pour surcharger les parametres :

```php
use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        ->headersEnabled(true)
        ->xmlrpcDisabled(true)
        ->hideVersion(true)
        ->disableFileEditor(true)
        ->restApiRequireAuth(true)
        ->passwordMinLength(16)
        ->maxConcurrentSessions(1);
};
```

### Ce qui se passe automatiquement

Avec cette configuration, le framework active :

1. **En-tetes HTTP securises** (`HttpHeadersHardening`) : X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS
2. **Durcissement du login** (`LoginHardening`) : throttling IP/compte, messages d'erreur generiques
3. **Desactivation XML-RPC** (`DisableXmlRpc`) : suppression du header X-Pingback et des liens RSD
4. **Masquage de version** (`HideWordPressVersion`) : suppression du meta generator et des parametres `ver=` sur les assets
5. **Editeur de fichiers desactive** (`DisableFileEditor`) : definition de `DISALLOW_FILE_EDIT`
6. **Cookies securises** (`CookieHardening`) : attributs Secure, HttpOnly, SameSite=Lax
7. **Audit de securite** (`SecurityAuditLogger`) : journalisation des evenements critiques
8. **Politique de mots de passe** (`PasswordPolicy`) : longueur minimale, majuscule, chiffre, caractere special requis

## Etape 4 : Activer l'authentification a deux facteurs (2FA)

Ajoutez l'activation de la 2FA dans votre configuration :

```php
return static function (SecurityConfigurator $security): void {
    $security
        ->twoFactorEnabled(true)
        ->twoFactorIssuer('MonApplication');
};
```

### Flux de configuration 2FA pour un utilisateur

La 2FA utilise TOTP (RFC 6238), compatible avec Google Authenticator, Authy, 1Password, etc.

1. **Initialisation** : `TwoFactorSetupManager::setup()` genere un secret et des codes de secours

```php
$setupManager = $container->get(TwoFactorSetupManager::class);

$result = $setupManager->setup($userId, $userEmail);
// $result['secret']           -> secret TOTP Base32
// $result['provisioning_uri'] -> URI otpauth:// pour QR code
// $result['backup_codes']     -> 8 codes de secours (format XXXX-XXXX)
```

2. **Confirmation** : l'utilisateur scanne le QR code et saisit un code pour confirmer

```php
$confirmed = $setupManager->confirmSetup($userId, $codeFromUser);
// true  -> 2FA active pour cet utilisateur
// false -> code invalide, 2FA non active
```

3. **Authentification** : `TwoFactorAuthentication` intercepte le login (priorite 40 sur le filtre `authenticate`) et exige un code TOTP ou un code de secours

4. **Codes de secours** : 8 codes a usage unique, haches avec bcrypt, verification en temps constant

```php
$newCodes = $setupManager->regenerateBackupCodes($userId);
```

5. **Desactivation** :

```php
$setupManager->disableForUser($userId);
```

## Etape 5 : Verifier l'installation

### Via le health check

Le framework inclut un health check securite accessible via REST API :

```
GET /wp-json/backto/v1/security/health
```

Il verifie que les regles critiques sont actives : `http_headers_hardening`, `disable_xmlrpc`, `hide_wordpress_version`, `login_hardening`, `upload_security`, `capability_hardening`, `security_audit_logger`, `disable_file_editor`.

### Via le journal d'audit

Accedez au journal d'audit dans l'administration WordPress sous **Audit Log** (menu avec l'icone bouclier). Vous pouvez filtrer par type d'evenement et severite, exporter en CSV et purger les anciens evenements.

## Etape suivante

Consultez les [guides pratiques](how-to.md) pour des recettes avancees comme la configuration CSP, le controle d'acces IP, le rate limiting et la configuration CORS pour WordPress headless.
