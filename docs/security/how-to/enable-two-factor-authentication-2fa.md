# Enable two-factor authentication (2FA)

Enable 2FA globally in your configuration:

```php
<?php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        ->twoFactorEnabled(true)
        ->twoFactorIssuer('MyApp');
};
```

### Set up 2FA for a user

```php
<?php

use BackTo\Framework\Bundle\Security\TwoFactor\TwoFactorSetupManager;

$setupManager = $container->get(TwoFactorSetupManager::class);

$result = $setupManager->setup($userId, $userEmail);
// $result['secret']           — TOTP secret (Base32)
// $result['provisioning_uri'] — otpauth:// URI for QR code
// $result['backup_codes']     — 8 one-time codes (XXXX-XXXX format)
```

### Confirm setup

The user scans the QR code and enters a code to confirm:

```php
$confirmed = $setupManager->confirmSetup($userId, $codeFromUser);
// true = 2FA is now active; false = invalid code, not activated
```

### Regenerate backup codes

```php
$newCodes = $setupManager->regenerateBackupCodes($userId);
```

### Disable 2FA for a user

```php
$setupManager->disableForUser($userId);
```
