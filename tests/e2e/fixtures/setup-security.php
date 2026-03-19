<?php
/**
 * MU-plugin that activates the Security module for E2E testing.
 *
 * Drop this into wp-content/mu-plugins/ via wp-env mapping
 * to configure AdminUrlObfuscation and LoginHardening for tests.
 *
 * Usage in .wp-env.override.json:
 *   "mappings": {
 *     "wp-content/mu-plugins/setup-security.php": "./tests/e2e/fixtures/setup-security.php"
 *   }
 */

// Only activate if BackTo Framework is loaded
if (!class_exists(\BackTo\Framework\Security\AdminUrlObfuscation::class)) {
    return;
}

add_action('plugins_loaded', function () {
    // Configure admin URL obfuscation for E2E tests
    if (has_filter('backto_security_config')) {
        return; // Already configured by the framework
    }

    add_filter('backto_security_config', function (array $config): array {
        // Set custom login slug for URL obfuscation tests
        $config['admin_url_obfuscation']['login_slug'] = 'acces-securise';

        // Set login throttle thresholds for brute force tests
        $config['login_hardening']['max_ip_attempts'] = 5;
        $config['login_hardening']['lockout_seconds'] = 900;

        return $config;
    });
}, 1);
