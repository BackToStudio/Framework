# Store consent per-user for logged-in visitors


Extend the consent storage to save choices in user meta for logged-in users:

```php
<?php

namespace MyPlugin\Gdpr;

use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentStorageInterface;
use BackTo\Framework\Bundle\Gdpr\Infrastructure\CookieConsentStorage;

final class HybridConsentStorage implements ConsentStorageInterface
{
    public function __construct(
        private readonly CookieConsentStorage $cookieStorage,
    ) {}

    public function getConsent(): array
    {
        $userId = get_current_user_id();
        if ($userId > 0) {
            $meta = get_user_meta($userId, 'gdpr_consent', true);
            if (is_array($meta) && !empty($meta)) {
                return $meta;
            }
        }

        return $this->cookieStorage->getConsent();
    }

    public function hasConsent(string $categoryKey): bool
    {
        $consent = $this->getConsent();
        return $consent[$categoryKey] ?? false;
    }

    public function isConsentGiven(): bool
    {
        $userId = get_current_user_id();
        if ($userId > 0) {
            return !empty(get_user_meta($userId, 'gdpr_consent', true));
        }

        return $this->cookieStorage->isConsentGiven();
    }
}
```

Override the DI binding to use your implementation:

```php
use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentStorageInterface;

$containerBuilder->register(ConsentStorageInterface::class, HybridConsentStorage::class)
    ->setAutowired(true);
```
