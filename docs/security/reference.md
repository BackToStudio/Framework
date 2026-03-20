# Security Bundle — API Reference

*Reference — Information-oriented*

**Namespace:** `BackTo\Framework\Bundle\Security`

---

## Configuration

### `SecurityConfigurator`

Fluent API used in `config/security.php`.

| Method | DI Parameter | Default |
|---|---|---|
| `headersEnabled(bool)` | `security.headers_enabled` | `true` |
| `xmlrpcDisabled(bool)` | `security.xmlrpc_disabled` | `true` |
| `hideVersion(bool)` | `security.hide_version` | `true` |
| `cspReportOnly(bool)` | `security.csp_report_only` | `false` |
| `passwordMinLength(int)` | `security.password_min_length` | `12` |
| `maxConcurrentSessions(int)` | `security.max_concurrent_sessions` | `1` |
| `restApiRequireAuth(bool)` | `security.rest_api_require_auth` | `true` |
| `disableFileEditor(bool)` | `security.disable_file_editor` | `true` |
| `twoFactorEnabled(bool)` | `security.two_factor_enabled` | `false` |
| `twoFactorIssuer(string)` | `security.two_factor_issuer` | `'WordPress'` |

---

## Security rules — Hooks

All rules implement `SecurityRuleInterface` (extends `HookInterface`).

### Login & authentication

| Class | Hook | Type | Priority |
|---|---|---|---|
| `LoginHardening` | `authenticate` | Filter | 30 |
| | `login_errors` | Filter | 10 |
| | `wp_login_failed` | Action | 10 |
| | `wp_login` | Action | 10 |
| `TwoFactor\TwoFactorAuthentication` | `authenticate` | Filter | 40 |
| `LoginAnomalyDetector` | `wp_login` | Action | 20 |
| `SessionManager` | `attach_session_information` | Filter | 10 |
| | `wp_login` | Action | 10 |
| | `session_token_manager` | Filter | 10 |
| `PasswordPolicy` | `user_profile_update_errors` | Action | 10 |
| | `registration_errors` | Action | 10 |
| `AdminUrlObfuscation` | `init` | Action | 10 |
| | `wp_loaded` | Action | 10 |
| | `login_url` | Filter | 10 |
| | `logout_url` | Filter | 10 |
| | `site_url` | Filter | 10 |
| `DisableUserEnumeration` | `init` | Action | 10 |
| | `rest_endpoints` | Filter | 10 |

### HTTP headers

| Class | Hook | Type | Priority |
|---|---|---|---|
| `HttpHeadersHardening` | `send_headers` | Action | 10 |
| | `rest_api_init` | Action | 10 |
| `SecurityHeadersConfigurator` | `send_headers` | Action | 10 |
| | `rest_api_init` | Action | 10 |
| `ContentSecurityPolicyManager` | `send_headers` | Action | 10 |
| | `script_loader_tag` | Filter | 10 |
| `SubresourceIntegrity` | `script_loader_tag` | Filter | 20 |
| | `style_loader_tag` | Filter | 20 |
| `CookieHardening` | `secure_auth_cookie` | Filter | 10 |
| | `secure_logged_in_cookie` | Filter | 10 |
| | `set_auth_cookie` | Action | 10 |
| | `set_logged_in_cookie` | Action | 10 |
| | `init` | Action | 10 |
| `HideWordPressVersion` | `the_generator` | Filter | 10 |
| | `style_loader_src` | Filter | 10 |
| | `script_loader_src` | Filter | 10 |
| | `wp_head` | Action | 1 |

### API & access control

| Class | Hook | Type | Priority |
|---|---|---|---|
| `RestApiSecurity` | `rest_authentication_errors` | Filter | 10 |
| | `rest_index` | Filter | 10 |
| `RestApiRateLimiter` | `rest_pre_dispatch` | Filter | 10 |
| `CorsManager` | `rest_api_init` | Action | 5 |
| | `rest_pre_serve_request` | Filter | 10 |
| `IPAccessControl` | `admin_init` | Action | 10 |
| | `login_init` | Action | 10 |

### Audit & monitoring

| Class | Hook | Type | Priority |
|---|---|---|---|
| `SecurityAuditLogger` | `wp_login` | Action | 10 |
| | `wp_login_failed` | Action | 10 |
| | `set_user_role` | Action | 10 |
| | `updated_option` | Action | 10 |
| | `activated_plugin` | Action | 10 |
| | `deactivated_plugin` | Action | 10 |
| | `switch_theme` | Action | 10 |
| | `user_register` | Action | 10 |
| | `delete_user` | Action | 10 |
| `SecurityNotifier` | `backto_security_event` | Action | 10 |
| | `set_user_role` | Action | 15 |
| | `wp_login_failed` | Action | 10 |
| `FileIntegrityMonitor` | `admin_init` | Action | 10 |
| | `backto_file_integrity_check` | Action | 10 |

### System hardening

| Class | Hook | Type | Priority |
|---|---|---|---|
| `DisableXmlRpc` | `xmlrpc_enabled` | Filter | 10 |
| | `wp_headers` | Filter | 10 |
| | `wp` | Action | 10 |
| `DisableFileEditor` | `init` | Action | 10 |
| `DisablePublicCron` | `init` | Action | 1 |
| `AutoUpdatePolicy` | `allow_major_auto_core_updates` | Filter | 10 |
| | `allow_minor_auto_core_updates` | Filter | 10 |
| | `auto_update_plugin` | Filter | 10 |
| | `auto_update_theme` | Filter | 10 |
| | `auto_update_translation` | Filter | 10 |
| `CapabilityHardening` | `set_user_role` | Action | 5 |
| | `user_has_cap` | Filter | 10 |
| `DatabaseHardening` | `query` | Filter | 10 |
| `CommentSpamProtection` | `comment_form` | Action | 10 |
| | `preprocess_comment` | Filter | 10 |
| `UploadSecurity` | `upload_mimes` | Filter | 10 |
| | `wp_handle_upload_prefilter` | Filter | 10 |
| `DirectoryProtection` | `admin_init` | Action | 10 |

---

## Two-factor authentication

**Namespace:** `BackTo\Framework\Bundle\Security\TwoFactor`

### `TwoFactorSetupManager`

| Method | Return | Description |
|---|---|---|
| `setup(int $userId, string $accountName)` | `array{secret, provisioning_uri, backup_codes}` | Generate secret and backup codes |
| `confirmSetup(int $userId, string $code)` | `bool` | Verify code and activate 2FA |
| `disableForUser(int $userId)` | `void` | Disable 2FA and delete all data |
| `regenerateBackupCodes(int $userId)` | `string[]` | Generate 8 new backup codes |
| `isEnabledForUser(int $userId)` | `bool` | Check if 2FA is active |

### `TotpProvider`

TOTP implementation (RFC 6238). Period: 30s, digits: 6, algorithm: HMAC-SHA1, tolerance: +/- 1 window.

| Method | Return | Description |
|---|---|---|
| `generateSecret(int $length = 20)` | `string` | Base32-encoded secret |
| `generateCode(string $secret, ?int $timestamp)` | `string` | 6-digit TOTP code |
| `verifyCode(string $secret, string $code, int $discrepancy = 1)` | `bool` | Verify with tolerance |
| `getProvisioningUri(string $secret, string $accountName, string $issuer)` | `string` | `otpauth://totp/...` URI |

### `BackupCodeManager`

Generates 8 codes in `XXXX-XXXX` format. Hashed with bcrypt. Verification iterates all codes in constant time.

---

## REST API routes

| Route | Method | Capability | Description |
|---|---|---|---|
| `/backto/v1/security/audit-log` | GET | `manage_options` | Audit log with filters |
| `/backto/v1/security/health` | GET | `manage_options` | Security health check |
| `/backto/v1/security/scan` | GET | `manage_options` | File integrity + malware scan |

---

## Contracts

| Interface | Default implementation |
|---|---|
| `SecurityRuleInterface` | All security rule classes |
| `ContentSecurityPolicyInterface` | `ContentSecurityPolicyManager` |
| `CorsManagerInterface` | `CorsManager` |
| `IPAccessControlInterface` | `IPAccessControl` |
| `SubresourceIntegrityInterface` | `SubresourceIntegrity` |
| `LoginThrottleInterface` | `WordPressLoginThrottle` |
| `AuditLogRepositoryInterface` | `WordPressAuditLogRepository` |
| `FileIntegrityRepositoryInterface` | `WordPressFileIntegrityRepository` |
| `RateLimiterRepositoryInterface` | `WordPressRateLimiterRepository` |
| `LoginLocationRepositoryInterface` | `WordPressLoginLocationRepository` |
| `TwoFactorRepositoryInterface` | `WordPressTwoFactorRepository` |
| `ClientIpResolverInterface` | `ClientIpResolver` |
| `NonceManagerInterface` | `WordPressNonceManager` |
| `InputSanitizerInterface` | `WordPressInputSanitizer` |
| `OutputEscaperInterface` | `WordPressOutputEscaper` |
| `MailerInterface` | `WordPressMailer` |
| `SecurityNotifierInterface` | `SecurityNotifier` |
| `TotpProviderInterface` | `TotpProvider` |
| `BackupCodeManagerInterface` | `BackupCodeManager` |

---

## DI registration

`SecurityExtension` registers the bundle. Auto-configuration tags all `SecurityRuleInterface` implementations with `wordpress.security_rule`. The `RegisterSecurityRulePass` compiler pass collects tagged services into the `SecurityRuleRegistry`.

```php
$containerBuilder->registerForAutoconfiguration(SecurityRuleInterface::class)
    ->addTag('wordpress.security_rule');
```
