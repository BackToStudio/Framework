<?php

declare(strict_types=1);

namespace BackTo\Framework\Tests\Integration;

use BackTo\Framework\Bundle\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressHtmlOptimizer;
use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressPageCache;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for the full page cache lifecycle.
 *
 * Simulates the complete workflow:
 * 1. Render a page (using realistic HTML from a default WordPress theme)
 * 2. Store it in the file-based page cache
 * 3. Retrieve the cached version
 * 4. Verify content integrity after caching
 * 5. Test invalidation and re-caching after content updates
 * 6. Combine with HTML minification
 *
 * This test runs standalone without WordPress. For full WP integration,
 * use: npm run test:integration (requires wp-env / Docker).
 */
class PageCacheIntegrationTest extends TestCase
{
    private WordPressPageCache $pageCache;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/btf_integration_cache_' . uniqid();
        $this->pageCache = new WordPressPageCache($this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->pageCache->flush();

        if (is_dir($this->cacheDir)) {
            rmdir($this->cacheDir);
        }
    }

    // -------------------------------------------------------
    // Realistic theme HTML rendering & caching
    // -------------------------------------------------------

    public function testCacheStoresFullThemeHomepage(): void
    {
        $html = $this->renderHomepage('Performance Test Post');

        $url = 'https://example.com/';
        $this->pageCache->put($url, $html, 3600);

        $cached = $this->pageCache->get($url);

        $this->assertNotNull($cached);
        $this->assertStringContainsString('<!DOCTYPE html>', $cached);
        $this->assertStringContainsString('Performance Test Post', $cached);
        $this->assertStringContainsString('Cached by BackTo Framework', $cached);
    }

    public function testCacheStoresFullThemeSinglePost(): void
    {
        $html = $this->renderSinglePost('Mon article de test', '<p>Contenu de l\'article pour le test de cache.</p>');

        $url = 'https://example.com/2026/03/mon-article-de-test/';
        $this->pageCache->put($url, $html, 3600);

        $cached = $this->pageCache->get($url);

        $this->assertNotNull($cached);
        $this->assertStringContainsString('Mon article de test', $cached);
        $this->assertStringContainsString('Contenu de l\'article', $cached);
        $this->assertStringContainsString('</html>', $cached);
    }

    // -------------------------------------------------------
    // Cache lifecycle: store, serve, invalidate, re-cache
    // -------------------------------------------------------

    public function testFullCacheLifecycle(): void
    {
        $url = 'https://example.com/sample-page/';

        // 1. First request: render and cache
        $originalHtml = $this->renderSinglePost('Original Title', '<p>Original content.</p>');
        $this->pageCache->put($url, $originalHtml, 3600);

        // 2. Second request: serve from cache (should be identical)
        $cachedHtml = $this->pageCache->get($url);
        $this->assertNotNull($cachedHtml);
        $this->assertStringContainsString('Original Title', $cachedHtml);

        // 3. Content update: invalidate cache
        $this->pageCache->invalidate($url);
        $this->assertNull($this->pageCache->get($url), 'Cache should be cleared after invalidation');

        // 4. Next request: render updated content and re-cache
        $updatedHtml = $this->renderSinglePost('Updated Title', '<p>Updated content with new information.</p>');
        $this->pageCache->put($url, $updatedHtml, 3600);

        // 5. Verify updated cache
        $reCachedHtml = $this->pageCache->get($url);
        $this->assertNotNull($reCachedHtml);
        $this->assertStringContainsString('Updated Title', $reCachedHtml);
        $this->assertStringNotContainsString('Original Title', $reCachedHtml);
    }

    // -------------------------------------------------------
    // Multiple pages cached independently
    // -------------------------------------------------------

    public function testMultiplePagesAreCachedIndependently(): void
    {
        $pages = [
            'https://example.com/'                => $this->renderHomepage('Homepage Post'),
            'https://example.com/about/'          => $this->renderSinglePost('About Us', '<p>About page content.</p>'),
            'https://example.com/contact/'        => $this->renderSinglePost('Contact', '<p>Contact form here.</p>'),
            'https://example.com/blog/first-post/' => $this->renderSinglePost('First Post', '<p>My first blog post.</p>'),
        ];

        // Cache all pages
        foreach ($pages as $url => $html) {
            $this->pageCache->put($url, $html, 3600);
        }

        // Verify each page is cached independently
        $cachedHome = $this->pageCache->get('https://example.com/');
        $cachedAbout = $this->pageCache->get('https://example.com/about/');
        $cachedContact = $this->pageCache->get('https://example.com/contact/');
        $cachedBlog = $this->pageCache->get('https://example.com/blog/first-post/');

        $this->assertStringContainsString('Homepage Post', $cachedHome);
        $this->assertStringContainsString('About Us', $cachedAbout);
        $this->assertStringContainsString('Contact', $cachedContact);
        $this->assertStringContainsString('First Post', $cachedBlog);

        // Invalidating one should not affect others
        $this->pageCache->invalidate('https://example.com/about/');
        $this->assertNull($this->pageCache->get('https://example.com/about/'));
        $this->assertNotNull($this->pageCache->get('https://example.com/'));
        $this->assertNotNull($this->pageCache->get('https://example.com/contact/'));
    }

    // -------------------------------------------------------
    // Cache + HTML minification pipeline
    // -------------------------------------------------------

    public function testCacheWithHtmlMinification(): void
    {
        $html = $this->renderHomepage('Minified Cache Test');
        $optimizer = new WordPressHtmlOptimizer();

        // Minify before caching (as MinifyHtml hook would do)
        $minified = $optimizer->optimize($html);

        $url = 'https://example.com/';
        $this->pageCache->put($url, $minified, 3600);

        $cached = $this->pageCache->get($url);

        $this->assertNotNull($cached);
        $this->assertStringContainsString('Minified Cache Test', $cached);

        // Minified cache should be smaller than original (accounting for cache comment)
        $originalCachedLength = strlen($html) + 100; // BackTo cache comment overhead
        $this->assertLessThan(
            $originalCachedLength,
            strlen($cached),
            'Minified+cached HTML should be smaller than original cached HTML would be'
        );

        // Verify the minifier removed redundant type attributes from non-preserved tags
        // The inline <script> content is preserved, but external <script src="..."> tags
        // get their type="text/javascript" stripped
        $this->assertStringContainsString('<script src=', $cached, 'External scripts should remain functional');
    }

    public function testMinifiedCacheRemovesHtmlComments(): void
    {
        $html = $this->renderHomepage('Comment Removal Test');
        $optimizer = new WordPressHtmlOptimizer();
        $minified = $optimizer->optimize($html);

        $url = 'https://example.com/comments/';
        $this->pageCache->put($url, $minified, 3600);

        $cached = $this->pageCache->get($url);

        // Original comment should be removed by minification
        $this->assertStringNotContainsString('should be removed by minification', $cached);
    }

    public function testMinificationPreservesThemeStructure(): void
    {
        $html = $this->renderHomepage('Structure Test');
        $optimizer = new WordPressHtmlOptimizer();

        $minified = $optimizer->optimize($html);

        // Critical elements must survive minification
        $this->assertStringContainsString('<!DOCTYPE html>', $minified);
        $this->assertStringContainsString('<html', $minified);
        $this->assertStringContainsString('<head', $minified);
        $this->assertStringContainsString('<body', $minified);
        $this->assertStringContainsString('</html>', $minified);
        $this->assertStringContainsString('Structure Test', $minified);

        // Script content should be preserved
        $this->assertStringContainsString('document.documentElement.classList', $minified);
    }

    // -------------------------------------------------------
    // TTL behavior
    // -------------------------------------------------------

    public function testExpiredCacheReturnsNull(): void
    {
        $url = 'https://example.com/expired/';
        $this->pageCache->put($url, $this->renderHomepage('Expired'), -1);

        $this->assertNull($this->pageCache->get($url));
    }

    public function testValidTtlServesCache(): void
    {
        $url = 'https://example.com/valid/';
        $this->pageCache->put($url, $this->renderHomepage('Valid'), 7200);

        $this->assertNotNull($this->pageCache->get($url));
    }

    // -------------------------------------------------------
    // Flush all
    // -------------------------------------------------------

    public function testFlushClearsAllCachedPages(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->pageCache->put(
                "https://example.com/page-{$i}/",
                $this->renderSinglePost("Page {$i}", "<p>Content {$i}</p>"),
                3600
            );
        }

        // Verify all cached
        for ($i = 1; $i <= 10; $i++) {
            $this->assertNotNull($this->pageCache->get("https://example.com/page-{$i}/"));
        }

        // Flush
        $this->pageCache->flush();

        // Verify all gone
        for ($i = 1; $i <= 10; $i++) {
            $this->assertNull($this->pageCache->get("https://example.com/page-{$i}/"));
        }
    }

    // -------------------------------------------------------
    // Cache content integrity
    // -------------------------------------------------------

    public function testCachePreservesSpecialCharacters(): void
    {
        $html = $this->renderSinglePost(
            'Spécial Chàracters & "Quotes"',
            '<p>Contenu avec des accents : é, è, ê, à, ü, ö, ñ, ß</p>'
        );

        $url = 'https://example.com/special/';
        $this->pageCache->put($url, $html, 3600);

        $cached = $this->pageCache->get($url);

        $this->assertStringContainsString('Spécial Chàracters', $cached);
        $this->assertStringContainsString('é, è, ê, à, ü, ö, ñ, ß', $cached);
    }

    public function testCachePreservesLargePages(): void
    {
        // Simulate a large page with lots of content
        $content = str_repeat('<p>' . str_repeat('Lorem ipsum dolor sit amet. ', 20) . '</p>', 50);
        $html = $this->renderSinglePost('Large Page Test', $content);

        $url = 'https://example.com/large/';
        $this->pageCache->put($url, $html, 3600);

        $cached = $this->pageCache->get($url);

        $this->assertNotNull($cached);
        $this->assertStringContainsString('Large Page Test', $cached);
        $this->assertStringContainsString('Lorem ipsum', $cached);
    }

    // -------------------------------------------------------
    // Helpers: realistic WordPress theme HTML output
    // -------------------------------------------------------

    /**
     * Generate realistic HTML output mimicking a WordPress default theme homepage.
     */
    private function renderHomepage(string $postTitle): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr-FR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$postTitle} &#8211; Mon Site WordPress</title>
            <link rel="dns-prefetch" href="//s.w.org">
            <link rel="stylesheet" id="wp-block-library-css" href="https://example.com/wp-includes/css/dist/block-library/style.min.css?ver=6.7" type="text/css" media="all">
            <link rel="stylesheet" id="twentytwentyfive-style-css" href="https://example.com/wp-content/themes/twentytwentyfive/style.css?ver=1.0" type="text/css" media="all">
            <script type="text/javascript" src="https://example.com/wp-includes/js/wp-emoji-release.min.js?ver=6.7" defer></script>
            <script type="text/javascript">
                document.documentElement.classList.remove('no-js');
                document.documentElement.classList.add('js');
            </script>
            <link rel="https://api.w.org/" href="https://example.com/wp-json/">
            <link rel="EditURI" type="application/rsd+xml" title="RSD" href="https://example.com/xmlrpc.php?rsd">
            <link rel="wlwmanifest" type="application/wlwmanifest+xml" href="https://example.com/wp-includes/wlwmanifest.xml">
            <meta name="generator" content="WordPress 6.7">
            <!-- This is a comment that should be removed by minification -->
        </head>
        <body class="home blog wp-embed-responsive">
            <div id="page" class="site">
                <header id="masthead" class="site-header">
                    <div class="site-branding">
                        <h1 class="site-title"><a href="https://example.com/">Mon Site WordPress</a></h1>
                        <p class="site-description">Un site utilisant WordPress</p>
                    </div>
                    <nav id="site-navigation" class="main-navigation">
                        <ul id="primary-menu" class="menu">
                            <li><a href="https://example.com/">Accueil</a></li>
                            <li><a href="https://example.com/about/">À propos</a></li>
                            <li><a href="https://example.com/contact/">Contact</a></li>
                        </ul>
                    </nav>
                </header>

                <main id="primary" class="site-main">
                    <article id="post-1" class="post type-post status-publish">
                        <header class="entry-header">
                            <h2 class="entry-title">
                                <a href="https://example.com/2026/03/{$postTitle}/">{$postTitle}</a>
                            </h2>
                        </header>
                        <div class="entry-content">
                            <p>Ceci est le contenu de l'article. Il utilise le thème par défaut de WordPress.</p>
                        </div>
                        <footer class="entry-footer">
                            <span class="posted-on">Publié le 15 mars 2026</span>
                        </footer>
                    </article>
                </main>

                <footer id="colophon" class="site-footer">
                    <div class="site-info">
                        <span class="site-title"><a href="https://example.com/">Mon Site WordPress</a></span>
                        <span>Propulsé par <a href="https://wordpress.org/">WordPress</a></span>
                    </div>
                </footer>
            </div>

            <script type="text/javascript" src="https://example.com/wp-includes/js/wp-embed.min.js?ver=6.7"></script>
        </body>
        </html>
        HTML;
    }

    /**
     * Generate realistic HTML output mimicking a WordPress single post page.
     */
    private function renderSinglePost(string $title, string $content): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr-FR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$title} &#8211; Mon Site WordPress</title>
            <link rel="stylesheet" id="twentytwentyfive-style-css" href="https://example.com/wp-content/themes/twentytwentyfive/style.css?ver=1.0" type="text/css" media="all">
            <script type="text/javascript">
                document.documentElement.classList.remove('no-js');
            </script>
        </head>
        <body class="single single-post">
            <div id="page" class="site">
                <header id="masthead" class="site-header">
                    <div class="site-branding">
                        <h1 class="site-title"><a href="https://example.com/">Mon Site WordPress</a></h1>
                    </div>
                </header>

                <main id="primary" class="site-main">
                    <article id="post-42" class="post type-post status-publish">
                        <header class="entry-header">
                            <h1 class="entry-title">{$title}</h1>
                            <div class="entry-meta">
                                <span class="posted-on">15 mars 2026</span>
                                <span class="byline">par Admin</span>
                            </div>
                        </header>
                        <div class="entry-content">
                            {$content}
                        </div>
                        <footer class="entry-footer">
                            <span class="cat-links">Catégorie : Non classé</span>
                        </footer>
                    </article>

                    <nav class="post-navigation">
                        <div class="nav-previous"><a href="#">Article précédent</a></div>
                        <div class="nav-next"><a href="#">Article suivant</a></div>
                    </nav>
                </main>

                <footer id="colophon" class="site-footer">
                    <span>Propulsé par <a href="https://wordpress.org/">WordPress</a></span>
                </footer>
            </div>
        </body>
        </html>
        HTML;
    }
}
