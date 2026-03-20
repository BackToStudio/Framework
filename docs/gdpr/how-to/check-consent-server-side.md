# Check consent server-side

Inject `ConsentStorageInterface` into your service:

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentStorageInterface;

class MyService
{
    public function __construct(
        private readonly ConsentStorageInterface $consentStorage,
    ) {}

    public function process(): void
    {
        if ($this->consentStorage->hasConsent('analytics')) {
            // User has accepted analytics cookies.
        }
    }
}
```
