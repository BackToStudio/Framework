# Two-factor authentication flow

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
