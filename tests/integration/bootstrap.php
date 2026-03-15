<?php

declare(strict_types=1);

/**
 * Integration test bootstrap for wp-env.
 *
 * When running inside wp-env (Docker + WordPress + MySQL), this bootstrap
 * loads the WordPress test framework and activates the default theme.
 *
 * When running standalone (no WordPress), it loads the Composer autoloader
 * and skips WordPress-dependent tests via the BTF_WP_LOADED constant.
 *
 * Usage:
 *   Inside wp-env:  npm run test:integration
 *   Standalone:     ./vendor/bin/phpunit --configuration=phpunit-integration.xml
 */

// Load the Composer autoloader for the framework
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// wp-env stores the test library in /wordpress-phpunit
$wpTestsDir = getenv('WP_TESTS_DIR') ?: '/wordpress-phpunit';

if (file_exists($wpTestsDir . '/includes/functions.php')) {
    /**
     * Load tests_add_filter() function.
     */
    require_once $wpTestsDir . '/includes/functions.php';

    /**
     * Set up the default theme before WordPress loads.
     */
    tests_add_filter('setup_theme', function (): void {
        $defaultThemes = [
            'twentytwentyfive',
            'twentytwentyfour',
            'twentytwentythree',
        ];

        foreach ($defaultThemes as $theme) {
            if (wp_get_theme($theme)->exists()) {
                switch_theme($theme);
                break;
            }
        }
    });

    /**
     * Start up the WP testing environment.
     */
    require_once $wpTestsDir . '/includes/bootstrap.php';

    define('BTF_WP_LOADED', true);

    echo "\n";
    echo "Active theme: " . wp_get_theme()->get('Name') . "\n";
    echo "WordPress version: " . get_bloginfo('version') . "\n";
    echo "\n";
} else {
    define('BTF_WP_LOADED', false);

    echo "\n";
    echo "WordPress test framework not found — running standalone integration tests only.\n";
    echo "For full WordPress integration tests, use wp-env: npm run test:integration\n";
    echo "\n";
}
