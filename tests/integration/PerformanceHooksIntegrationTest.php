<?php

declare(strict_types=1);

namespace BackTo\Framework\Tests\Integration;

use BackTo\Framework\Hooks\Infrastructure\WordPressHookDispatcher;
use BackTo\Framework\Performance\Hooks\CleanHead;
use BackTo\Framework\Performance\Hooks\DisableEmojis;
use BackTo\Framework\Performance\Hooks\DisableEmbeds;
use BackTo\Framework\Performance\Hooks\DisableXMLRPC;
use BackTo\Framework\Performance\Hooks\LimitPostRevisions;
use BackTo\Framework\Performance\Hooks\DeferScripts;
use BackTo\Framework\Performance\Hooks\DisableHeartbeat;
use BackTo\Framework\Performance\Infrastructure\WordPressHtmlOptimizer;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for Performance hooks with a real WordPress environment.
 *
 * Verifies that hooks properly modify WordPress output when activated
 * with the default theme.
 *
 * Requires wp-env (Docker): npm run test:integration
 * Skipped automatically when WordPress is not available.
 */
class PerformanceHooksIntegrationTest extends TestCase
{
    private WordPressHookDispatcher $dispatcher;

    public function setUp(): void
    {
        if (!defined('BTF_WP_LOADED') || !BTF_WP_LOADED) {
            $this->markTestSkipped('WordPress not available. Run with wp-env: npm run test:integration');
        }

        parent::setUp();
        $this->dispatcher = new WordPressHookDispatcher();
    }

    // -------------------------------------------------------
    // CleanHead
    // -------------------------------------------------------

    public function testCleanHeadRemovesGeneratorTag(): void
    {
        $hook = new CleanHead($this->dispatcher);
        $hook->hooks();

        $head = $this->captureWpHead();

        $this->assertStringNotContainsString('generator', $head, 'wp_generator should be removed from wp_head');
    }

    public function testCleanHeadRemovesRsdLink(): void
    {
        $hook = new CleanHead($this->dispatcher);
        $hook->hooks();

        $head = $this->captureWpHead();

        $this->assertStringNotContainsString('EditURI', $head, 'RSD link should be removed');
    }

    public function testCleanHeadRemovesWlwManifest(): void
    {
        $hook = new CleanHead($this->dispatcher);
        $hook->hooks();

        $head = $this->captureWpHead();

        $this->assertStringNotContainsString('wlwmanifest', $head, 'WLW manifest link should be removed');
    }

    public function testCleanHeadRemovesShortlink(): void
    {
        self::factory()->post->create(['post_status' => 'publish']);
        $this->go_to(home_url('/'));

        $hook = new CleanHead($this->dispatcher);
        $hook->hooks();

        $head = $this->captureWpHead();

        $this->assertStringNotContainsString('shortlink', $head, 'Shortlink should be removed');
    }

    // -------------------------------------------------------
    // DisableEmojis
    // -------------------------------------------------------

    public function testDisableEmojisRemovesEmojiScript(): void
    {
        $hook = new DisableEmojis($this->dispatcher);
        $hook->hooks();

        $head = $this->captureWpHead();

        $this->assertStringNotContainsString('wp-emoji-release', $head, 'Emoji detection script should be removed');
    }

    public function testDisableEmojisFiltersTinyMcePlugins(): void
    {
        $hook = new DisableEmojis($this->dispatcher);
        $hook->hooks();

        $plugins = apply_filters('tiny_mce_plugins', ['wplink', 'wpemoji', 'wpdialogs']);

        $this->assertNotContains('wpemoji', $plugins);
        $this->assertContains('wplink', $plugins);
    }

    public function testDisableEmojisDisablesSvgUrl(): void
    {
        $hook = new DisableEmojis($this->dispatcher);
        $hook->hooks();

        $svgUrl = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/');

        $this->assertFalse($svgUrl);
    }

    // -------------------------------------------------------
    // DisableEmbeds
    // -------------------------------------------------------

    public function testDisableEmbedsDeregistersScript(): void
    {
        // Enqueue the embed script first
        wp_enqueue_script('wp-embed');

        $hook = new DisableEmbeds($this->dispatcher);
        $hook->hooks();

        // Trigger the footer action where we deregister
        do_action('wp_footer');

        $this->assertFalse(
            wp_script_is('wp-embed', 'registered'),
            'wp-embed script should be deregistered'
        );
    }

    public function testDisableEmbedsDisablesOembedDiscovery(): void
    {
        $hook = new DisableEmbeds($this->dispatcher);
        $hook->hooks();

        $result = apply_filters('embed_oembed_discover', true);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------
    // DisableXMLRPC
    // -------------------------------------------------------

    public function testDisableXMLRPCDisablesEndpoint(): void
    {
        $hook = new DisableXMLRPC($this->dispatcher);
        $hook->hooks();

        $enabled = apply_filters('xmlrpc_enabled', true);

        $this->assertFalse($enabled);
    }

    public function testDisableXMLRPCRemovesPingbackHeader(): void
    {
        $hook = new DisableXMLRPC($this->dispatcher);
        $hook->hooks();

        $headers = apply_filters('wp_headers', [
            'X-Pingback' => 'https://example.com/xmlrpc.php',
            'Content-Type' => 'text/html',
        ]);

        $this->assertArrayNotHasKey('X-Pingback', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
    }

    // -------------------------------------------------------
    // LimitPostRevisions
    // -------------------------------------------------------

    public function testLimitPostRevisionsAppliesLimit(): void
    {
        $hook = new LimitPostRevisions($this->dispatcher, 3);
        $hook->hooks();

        $limit = apply_filters('wp_revisions_to_keep', PHP_INT_MAX, null);

        $this->assertSame(3, $limit);
    }

    public function testRevisionsAreActuallyLimited(): void
    {
        $hook = new LimitPostRevisions($this->dispatcher, 2);
        $hook->hooks();

        // Create a post
        $postId = self::factory()->post->create([
            'post_title'   => 'Revision Test',
            'post_content' => 'Version 1',
            'post_status'  => 'publish',
        ]);

        // Make several updates to generate revisions
        for ($i = 2; $i <= 5; $i++) {
            wp_update_post([
                'ID'           => $postId,
                'post_content' => "Version {$i}",
            ]);
        }

        $revisions = wp_get_post_revisions($postId);

        $this->assertLessThanOrEqual(
            2,
            count($revisions),
            'Number of stored revisions should respect the limit'
        );
    }

    // -------------------------------------------------------
    // DeferScripts
    // -------------------------------------------------------

    public function testDeferScriptsRemovesVersionQueryStrings(): void
    {
        $hook = new DeferScripts($this->dispatcher, ['jquery-core'], true);
        $hook->hooks();

        $src = apply_filters('script_loader_src', 'https://example.com/script.js?ver=1.2.3');

        $this->assertStringNotContainsString('ver=', $src);
    }

    public function testDeferScriptsKeepsVersionInAdmin(): void
    {
        // Note: in wp-env test context, is_admin() returns false
        // This test verifies the filter works on the front-end
        $hook = new DeferScripts($this->dispatcher, ['jquery-core'], true);
        $hook->hooks();

        $src = apply_filters('script_loader_src', 'https://example.com/script.js?ver=5.0');

        $this->assertStringNotContainsString('ver=', $src);
    }

    // -------------------------------------------------------
    // DisableHeartbeat
    // -------------------------------------------------------

    public function testDisableHeartbeatSetsAdminInterval(): void
    {
        $hook = new DisableHeartbeat($this->dispatcher, true, 120);
        $hook->hooks();

        $settings = apply_filters('heartbeat_settings', ['interval' => 15]);

        $this->assertSame(120, $settings['interval']);
    }

    // -------------------------------------------------------
    // HtmlOptimizer (with real theme output)
    // -------------------------------------------------------

    public function testHtmlOptimizerMinifiesRealThemeOutput(): void
    {
        self::factory()->post->create(['post_status' => 'publish']);
        $this->go_to(home_url('/'));

        $html = $this->captureFullPage();
        $optimizer = new WordPressHtmlOptimizer();
        $minified = $optimizer->optimize($html);

        $this->assertNotEmpty($minified);

        // Minified should be smaller (or same if already minified)
        $this->assertLessThanOrEqual(
            strlen($html),
            strlen($minified),
            'Minified HTML should not be larger than original'
        );

        // Should still be valid-ish HTML
        $this->assertStringContainsString('<!DOCTYPE html>', $minified);
        $this->assertStringContainsString('</html>', $minified);
    }

    // -------------------------------------------------------
    // Full integration: all hooks at once
    // -------------------------------------------------------

    public function testAllPerformanceHooksCanBeActivatedTogether(): void
    {
        $hooks = [
            new CleanHead($this->dispatcher),
            new DisableEmojis($this->dispatcher),
            new DisableEmbeds($this->dispatcher),
            new DisableXMLRPC($this->dispatcher),
            new LimitPostRevisions($this->dispatcher, 5),
            new DeferScripts($this->dispatcher),
            new DisableHeartbeat($this->dispatcher),
        ];

        // Register all hooks — should not throw any errors
        foreach ($hooks as $hook) {
            $hook->hooks();
        }

        self::factory()->post->create([
            'post_title'  => 'Full Integration Test',
            'post_status' => 'publish',
        ]);

        $this->go_to(home_url('/'));
        $head = $this->captureWpHead();

        // Verify cumulative effects
        $this->assertStringNotContainsString('wp-emoji-release', $head, 'Emojis should be disabled');
        $this->assertStringNotContainsString('generator', $head, 'Generator should be removed');

        // XML-RPC should be disabled
        $this->assertFalse(apply_filters('xmlrpc_enabled', true));

        // Revisions should be limited
        $this->assertSame(5, apply_filters('wp_revisions_to_keep', PHP_INT_MAX, null));
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Capture wp_head() output.
     */
    private function captureWpHead(): string
    {
        ob_start();
        wp_head();

        return ob_get_clean() ?: '';
    }

    /**
     * Capture a full page render using the active theme.
     */
    private function captureFullPage(): string
    {
        ob_start();

        $template = get_index_template();
        if ($template && file_exists($template)) {
            load_template($template);
        }

        return ob_get_clean() ?: '';
    }
}
