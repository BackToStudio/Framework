# Security Bundle

Le bundle Security du BackTo Framework fournit une protection en profondeur pour WordPress. Il couvre plus de 40 classes organisees autour du durcissement du login, de l'authentification a deux facteurs (TOTP RFC 6238), des en-tetes HTTP securises, de l'audit de securite, de l'integrite des fichiers, du controle d'acces et bien plus.

**Namespace :** `BackTo\Framework\Bundle\Security`

## Documentation

| Document | Description |
|----------|-------------|
| [Tutoriel](tutorial.md) | Demarrer avec le bundle Security : activer la securite dans un plugin, configurer le durcissement de base et activer la 2FA |
| [Guides pratiques](how-to.md) | Recettes concretes : CSP, controle IP, rate limiting, audit, CORS, politique de mots de passe, mises a jour automatiques, restriction REST API |
| [Reference](reference.md) | Reference complete de toutes les classes, hooks enregistres et options de configuration |
| [Explication](explanation.md) | Architecture : defense en profondeur, pattern SecurityRuleInterface, priorites des hooks, design de l'audit trail, flux 2FA, pattern hexagonal |

## Fonctionnalites principales

- **Durcissement du login** : throttling par IP et par compte, messages d'erreur generiques, detection d'anomalies de connexion
- **2FA (TOTP)** : implementation RFC 6238, codes de secours, QR code provisioning
- **En-tetes HTTP** : CSP avec nonces, HSTS, X-Frame-Options, Permissions-Policy, SRI
- **Audit de securite** : journalisation persistante, page admin, export CSV, notifications email
- **Integrite des fichiers** : baseline SHA-256, scan malware des uploads
- **Controle d'acces** : IP whitelist/blacklist CIDR, rate limiting REST API, restriction des endpoints
- **Durcissement systeme** : cookies securises, desactivation XML-RPC/editeur de fichiers/cron public, masquage de version, protection des uploads
