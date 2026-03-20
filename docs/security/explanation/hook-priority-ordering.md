# Hook priority ordering

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
