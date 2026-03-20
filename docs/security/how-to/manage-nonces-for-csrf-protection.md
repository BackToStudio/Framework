# Manage nonces for CSRF protection


Use `NonceManagerInterface` to create and verify nonces without coupling to WordPress functions:

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\NonceManagerInterface;

class AjaxHandler
{
    public function __construct(
        private readonly NonceManagerInterface $nonceManager,
    ) {}

    public function handleRequest(): void
    {
        $nonce = $_POST[$this->nonceManager->getFieldName()] ?? '';

        if (!$this->nonceManager->verify($nonce, 'my_ajax_action')) {
            wp_send_json_error('Invalid security token.', 403);
        }

        // Process the request safely...
    }

    public function getFormToken(): string
    {
        return $this->nonceManager->create('my_ajax_action');
    }
}
```

The field name defaults to `_backto_nonce`. Nonces are valid for 12-24 hours (WordPress default).
