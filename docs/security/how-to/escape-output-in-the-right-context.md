# Escape output in the right context

Use `OutputEscaperInterface` for context-aware output escaping:

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\OutputEscaperInterface;

class TemplateHelper
{
    public function __construct(
        private readonly OutputEscaperInterface $escaper,
    ) {}

    public function renderUserProfile(array $user): string
    {
        $name = $this->escaper->html($user['name']);
        $bio = $this->escaper->html($user['bio']);
        $website = $this->escaper->url($user['website']);
        $tooltip = $this->escaper->attr($user['tooltip']);
        $jsName = $this->escaper->js($user['name']);

        return <<<HTML
        <div class="profile" title="{$tooltip}">
            <h2>{$name}</h2>
            <p>{$bio}</p>
            <a href="{$website}">Website</a>
            <script>console.log('User: {$jsName}');</script>
        </div>
        HTML;
    }
}
```

Methods: `html()`, `attr()`, `url()`, `js()`, `textarea()`. Each applies the correct WordPress escaping function for its context.
