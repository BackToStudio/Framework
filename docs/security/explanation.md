# Security Bundle — Architecture & Design

*Explanation — Understanding-oriented*

---

## Defense in depth

The bundle applies **defense in depth**: each security layer operates independently. If one layer is bypassed, the others remain active. The layers are ordered by the request lifecycle:

```
Incoming request
  |
  v
[1. HTTP headers]         HttpHeadersHardening, CSP, CookieHardening
  |
  v
[2. Access control]       IPAccessControl, RestApiSecurity, DisableXmlRpc
  |
  v
[3. Rate limiting]        RestApiRateLimiter
  |
  v
[4. Authentication]       LoginHardening (throttle) -> 2FA -> SessionManager
  |
  v
[5. Authorization]        CapabilityHardening, DisableFileEditor
  |
  v
[6. Input validation]     DatabaseHardening, UploadSecurity, CommentSpamProtection
  |
  v
[7. Monitoring]           SecurityAuditLogger, LoginAnomalyDetector, FileIntegrityMonitor
  |
  v
[8. Notification]         SecurityNotifier
```

Even if an attacker bypasses rate limiting (layer 3), they still face login throttling and 2FA (layer 4), capability restrictions (layer 5), and every attempt is logged by layer 7.

---

## The SecurityRuleInterface pattern

### Problem

How do you add new security rules without modifying existing code, while ensuring all rules are activated at boot?

### Solution

The pattern has three parts:

1. **`SecurityRuleInterface`** extends `HookInterface` and adds `getName(): string`. It marks a class as a security rule.

2. **Auto-configuration:** `SecurityExtension` tags every `SecurityRuleInterface` implementation with `wordpress.security_rule` automatically.

3. **`RegisterSecurityRulePass`:** This compiler pass collects all tagged services and injects them into `SecurityRuleRegistry`.

The result is **Open/Closed**: adding a rule means creating a class. No configuration changes, no registration code. The registry also powers `SecurityHealthCheck`, which verifies that critical rules are present at runtime.

---

## Hook priority ordering

Several WordPress hooks are shared across multiple security rules. The bundle defines an explicit priority order to ensure correct execution:

### `authenticate` filter chain

```
Priority 20: WordPress core (username/password validation)
Priority 30: LoginHardening (IP and account throttling)
Priority 40: TwoFactorAuthentication (TOTP/backup code verification)
```

Throttling runs before 2FA to reject brute-force attempts cheaply. 2FA only executes when credentials are valid.

### `set_user_role` action chain

```
Priority  5: CapabilityHardening (blocks self-promotion, reverts the role)
Priority 10: SecurityAuditLogger (logs the change)
Priority 15: SecurityNotifier (sends email alert)
```

The capability check runs first to prevent the change. If it proceeds, it is logged and then reported.

---

## Hexagonal architecture

The bundle follows the **ports and adapters** pattern used throughout the framework. Security rules depend on port interfaces (in `Contracts/`), never on WordPress functions directly.

**Ports** define what the domain needs: throttle tracking, audit storage, nonce management, mail delivery, IP resolution. **Adapters** (in `Infrastructure/`) implement those ports using WordPress APIs: transients, `wp_options`, `user_meta`, `wp_mail`, and `wp_create_nonce`.

This separation means:

- **Testability** — Unit tests mock the port interfaces. No WordPress bootstrap required.
- **Substitutability** — Swapping storage (e.g., Redis instead of transients) means writing one adapter, not changing any rule.
- **Clarity** — Reading a rule's constructor signature tells you exactly what it depends on.

---

## Two-factor authentication flow

The 2FA implementation uses the `authenticate` filter at priority 40. It does not add an intermediate page. Instead, it returns a `WP_Error` with code `two_factor_required`, which signals the login form to display the 2FA field.

```
Login request
  -> WordPress core validates credentials (priority 20)
  -> LoginHardening checks throttle (priority 30)
  -> TwoFactorAuthentication checks 2FA status (priority 40)
     -> If 2FA enabled and no code: return WP_Error('two_factor_required')
     -> If 2FA enabled and code present: verify TOTP or backup code
     -> If valid: return WP_User (login succeeds)
```

Responsibilities are split across dedicated classes: `TwoFactorAuthentication` handles login interception only, `TwoFactorSetupManager` handles the lifecycle (setup, confirm, disable), `TotpProvider` implements the RFC 6238 algorithm, and `BackupCodeManager` handles backup code generation and constant-time verification.

---

## Audit trail design

The audit system uses the Repository pattern:

```
SecurityAuditLogger -> AuditLogRepositoryInterface -> WordPressAuditLogRepository
```

Three severity levels (`Info`, `Warning`, `Critical`) are sufficient for security events. Only a curated set of WordPress options (`siteurl`, `home`, `admin_email`, `users_can_register`, `default_role`, `permalink_structure`, `blogdescription`) are monitored to avoid noise from transient updates.

The notification chain flows through a WordPress action: `SecurityAuditLogger` dispatches `backto_security_event`, which `SecurityNotifier` listens to. Critical events trigger email alerts formatted by `SecurityAlertFormatter` and sent through `MailerInterface`.

---

## Why generic login error messages?

`LoginHardening` replaces WordPress's default login errors with a generic message. WordPress normally says "The password you entered for the username X is incorrect," which confirms the username exists. The bundle returns the same message regardless of whether the username or password was wrong, preventing username enumeration through the login form.

---

## Session management trade-offs

`SessionManager` limits concurrent sessions per user (default: 1). When a new session exceeds the limit, the oldest sessions are destroyed. This prevents credential sharing and limits the blast radius of a compromised password. The implementation runs two passes to handle race conditions where a new session is created between reading and deleting existing ones.
