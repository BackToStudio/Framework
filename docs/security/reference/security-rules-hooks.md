# Security rules — Hooks

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
