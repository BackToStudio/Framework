# Two-factor authentication

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
