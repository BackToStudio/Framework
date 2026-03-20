# Handle form submissions with nonce validation

Use `NonceManagerInterface` from the Security bundle to protect admin forms:

```php
<?php

namespace MyPlugin\Admin;

use BackTo\Framework\Bundle\Security\Contracts\NonceManagerInterface;
use BackTo\Framework\Bundle\Security\Contracts\InputSanitizerInterface;

final class SettingsHandler
{
    public function __construct(
        private readonly NonceManagerInterface $nonceManager,
        private readonly InputSanitizerInterface $sanitizer,
    ) {}

    public function renderForm(): void
    {
        $nonce = $this->nonceManager->create('my_plugin_save_settings');
        $currentValue = get_option('my_plugin_api_key', '');

        echo '<form method="post">';
        echo '<input type="hidden" name="_backto_nonce" value="' . esc_attr($nonce) . '">';
        echo '<table class="form-table"><tr>';
        echo '<th><label for="api_key">API Key</label></th>';
        echo '<td><input type="text" id="api_key" name="api_key" value="' . esc_attr($currentValue) . '" class="regular-text"></td>';
        echo '</tr></table>';
        submit_button('Save Settings');
        echo '</form>';
    }

    public function handleSubmission(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $nonce = $_POST['_backto_nonce'] ?? '';
        if (!$this->nonceManager->verify($nonce, 'my_plugin_save_settings')) {
            wp_die('Security check failed.');
        }

        $apiKey = $this->sanitizer->sanitizeText($_POST['api_key'] ?? '');
        update_option('my_plugin_api_key', $apiKey);

        add_settings_error('my_plugin', 'settings_updated', 'Settings saved.', 'updated');
    }
}
```
