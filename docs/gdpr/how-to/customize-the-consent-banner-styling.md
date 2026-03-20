# Customize the consent banner styling

The banner uses inline CSS rendered by `ConsentBannerRenderer`. To override styles, add your own CSS after the banner:

```php
<?php

namespace MyTheme\Gdpr;

use BackTo\Framework\Contracts\HookDispatcherInterface;

final class CustomBannerStyles
{
    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
    ) {}

    public function hooks(): void
    {
        // Run after the banner renders (priority > 100)
        $this->hookDispatcher->addAction('wp_footer', [$this, 'addStyles'], 101);
    }

    public function addStyles(): void
    {
        echo '<style>
            #gdpr-consent-banner {
                font-family: "Inter", sans-serif;
                background: #1a1a2e;
                border-top: 3px solid #e94560;
            }
            #gdpr-consent-banner h3 {
                color: #eee;
            }
            #gdpr-consent-banner .gdpr-btn-accept {
                background: #e94560;
                border-radius: 8px;
            }
            #gdpr-consent-banner .gdpr-btn-reject {
                background: transparent;
                border: 1px solid #e94560;
                color: #e94560;
                border-radius: 8px;
            }
        </style>';
    }
}
```

The banner uses z-index `999999` and is fixed at the bottom of the viewport. Override selectors with higher specificity if needed.
