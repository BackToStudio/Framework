# Set a password policy

```php
<?php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security->passwordMinLength(16);
};
```

The `PasswordPolicy` rule enforces: minimum length (configurable), at least one uppercase letter, one digit, and one special character. Validation runs on `user_profile_update_errors` and `registration_errors`.
