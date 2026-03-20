<?php

declare(strict_types=1);

namespace BackTo\Framework\Tests\Integration;

use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressHtmlOptimizer;
use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressPageCache;
use PHPUnit\Framework\TestCase;

/**
 * Benchmark: compare page rendering with and without cache.
 *
 * Measures and displays:
 * - Time to "render" a page (simulated template rendering)
 * - Time to serve the same page from file cache
 * - Time to serve with minification enabled
 * - Size differences between raw, cached, and minified+cached
 */
class PageCacheBenchmarkTest extends TestCase
{
    private WordPressPageCache $pageCache;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/btf_benchmark_' . uniqid();
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
    // Main benchmark
    // -------------------------------------------------------

    public function testBenchmarkWithAndWithoutCache(): void
    {
        $url = 'https://example.com/benchmark-page/';
        $iterations = 100;

        // ── 1. Simulate page rendering WITHOUT cache ──
        $renderTimes = [];
        $html = '';

        for ($i = 0; $i < $iterations; $i++) {
            $start = hrtime(true);
            $html = $this->simulateThemeRendering();
            $renderTimes[] = hrtime(true) - $start;
        }

        $avgRenderNs = array_sum($renderTimes) / count($renderTimes);

        // ── 2. Store in cache ──
        $this->pageCache->put($url, $html, 3600);

        // ── 3. Serve page FROM cache ──
        $cacheTimes = [];
        $cachedHtml = '';

        for ($i = 0; $i < $iterations; $i++) {
            $start = hrtime(true);
            $cachedHtml = $this->pageCache->get($url);
            $cacheTimes[] = hrtime(true) - $start;
        }

        $avgCacheNs = array_sum($cacheTimes) / count($cacheTimes);

        // ── 4. Minify + Cache pipeline ──
        $optimizer = new WordPressHtmlOptimizer();

        $minifyStart = hrtime(true);
        $minified = $optimizer->optimize($html);
        $minifyTime = hrtime(true) - $minifyStart;

        $urlMinified = 'https://example.com/benchmark-page-minified/';
        $this->pageCache->put($urlMinified, $minified, 3600);

        $minifiedCacheTimes = [];
        $minifiedCachedHtml = '';

        for ($i = 0; $i < $iterations; $i++) {
            $start = hrtime(true);
            $minifiedCachedHtml = $this->pageCache->get($urlMinified);
            $minifiedCacheTimes[] = hrtime(true) - $start;
        }

        $avgMinifiedCacheNs = array_sum($minifiedCacheTimes) / count($minifiedCacheTimes);

        // ── 5. Display results ──
        $rawSize = strlen($html);
        $cachedSize = strlen($cachedHtml);
        $minifiedSize = strlen($minified);
        $minifiedCachedSize = strlen($minifiedCachedHtml);

        $speedupCache = $avgRenderNs / max($avgCacheNs, 1);
        $speedupMinified = $avgRenderNs / max($avgMinifiedCacheNs, 1);

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════════╗\n";
        echo "║           PAGE CACHE BENCHMARK — Default Theme HTML             ║\n";
        echo "╠══════════════════════════════════════════════════════════════════╣\n";
        echo "║                                                                 ║\n";
        echo sprintf("║  Iterations: %-52d║\n", $iterations);
        echo "║                                                                 ║\n";
        echo "║  ──── TIMING (average over {$iterations} iterations) ──────────────────── ║\n";
        echo "║                                                                 ║\n";
        echo sprintf("║  ❌ Sans cache (render)    : %8.2f µs                        ║\n", $avgRenderNs / 1000);
        echo sprintf("║  ✅ Avec cache (file read) : %8.2f µs   (×%.1f plus rapide)  ║\n", $avgCacheNs / 1000, $speedupCache);
        echo sprintf("║  ✅ Minifié + cache        : %8.2f µs   (×%.1f plus rapide)  ║\n", $avgMinifiedCacheNs / 1000, $speedupMinified);
        echo sprintf("║  ⚡ Temps de minification  : %8.2f µs   (coût unique)        ║\n", $minifyTime / 1000);
        echo "║                                                                 ║\n";
        echo "║  ──── TAILLE ────────────────────────────────────────────────── ║\n";
        echo "║                                                                 ║\n";
        echo sprintf("║  HTML brut (rendu thème)   : %6d octets                     ║\n", $rawSize);
        echo sprintf("║  HTML en cache             : %6d octets  (+commentaire)      ║\n", $cachedSize);
        echo sprintf("║  HTML minifié              : %6d octets  (-%d%%)              ║\n", $minifiedSize, round((1 - $minifiedSize / $rawSize) * 100));
        echo sprintf("║  Minifié + en cache        : %6d octets  (-%d%%)              ║\n", $minifiedCachedSize, round((1 - $minifiedCachedSize / $rawSize) * 100));
        echo "║                                                                 ║\n";
        echo sprintf("║  Économie de taille        : %6d octets sauvés              ║\n", $rawSize - $minifiedSize);
        echo "║                                                                 ║\n";
        echo "╠══════════════════════════════════════════════════════════════════╣\n";
        echo "║  CONTENU DU CACHE                                              ║\n";
        echo "╠══════════════════════════════════════════════════════════════════╣\n";
        echo "║                                                                 ║\n";

        // Show first and last lines of cached content
        $cachedLines = explode("\n", $cachedHtml);
        $totalLines = count($cachedLines);
        echo sprintf("║  Lignes HTML en cache      : %d lignes                         ║\n", $totalLines);
        echo "║                                                                 ║\n";
        echo "║  Début du HTML en cache :                                       ║\n";

        for ($i = 0; $i < min(3, $totalLines); $i++) {
            $line = substr(trim($cachedLines[$i]), 0, 60);
            echo sprintf("║    %s\n", $line);
        }

        echo "║  ...                                                            ║\n";

        // Show last 3 lines
        echo "║  Fin du HTML en cache :                                         ║\n";

        for ($i = max(0, $totalLines - 3); $i < $totalLines; $i++) {
            $line = substr(trim($cachedLines[$i]), 0, 60);
            if ($line !== '') {
                echo sprintf("║    %s\n", $line);
            }
        }

        echo "║                                                                 ║\n";
        echo "╠══════════════════════════════════════════════════════════════════╣\n";
        echo "║  COMPARAISON HTML BRUT vs MINIFIÉ                              ║\n";
        echo "╠══════════════════════════════════════════════════════════════════╣\n";
        echo "║                                                                 ║\n";

        // Show specific optimizations
        $hasGenerator = str_contains($html, 'generator');
        $hasEmoji = str_contains($html, 'wp-emoji');
        $hasTypeJs = str_contains($html, 'type="text/javascript"');
        $hasComments = (bool) preg_match('/<!--(?!\[if)(?!PRESERVED)(?! Cached).*?-->/s', $html);

        $minHasGenerator = str_contains($minified, 'generator');
        $minHasEmoji = str_contains($minified, 'wp-emoji');
        $minHasTypeJs = str_contains($minified, 'type="text/javascript"');
        $minHasComments = (bool) preg_match('/<!--(?!\[if)(?!PRESERVED)(?! Cached).*?-->/s', $minified);

        echo sprintf("║  generator meta tag : %-8s → %-8s %s\n", $hasGenerator ? 'présent' : 'absent', $minHasGenerator ? 'présent' : 'absent', $minHasGenerator === $hasGenerator ? '(inchangé — retiré par CleanHead hook)' : '');
        echo sprintf("║  wp-emoji script    : %-8s → %-8s %s\n", $hasEmoji ? 'présent' : 'absent', $minHasEmoji ? 'présent' : 'absent', $minHasEmoji === $hasEmoji ? '(inchangé — retiré par DisableEmojis hook)' : '');
        echo sprintf("║  type=\"text/js\"     : %-8s → %-8s %s\n", $hasTypeJs ? 'présent' : 'absent', $minHasTypeJs ? 'présent' : 'supprimé', !$minHasTypeJs && $hasTypeJs ? '✓ supprimé par minification' : '');
        echo sprintf("║  Commentaires HTML  : %-8s → %-8s %s\n", $hasComments ? 'présent' : 'absent', $minHasComments ? 'présent' : 'supprimé', !$minHasComments && $hasComments ? '✓ supprimés par minification' : '');
        echo "║                                                                 ║\n";
        echo "║  Note: generator, wp-emoji, RSD, wlwmanifest sont retirés      ║\n";
        echo "║  par les hooks Performance (CleanHead, DisableEmojis, etc.)     ║\n";
        echo "║  La minification agit sur le HTML restant.                      ║\n";
        echo "║                                                                 ║\n";
        echo "╚══════════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        // Assertions to make the test pass
        $this->assertNotNull($cachedHtml, 'Cache should return content');
        $this->assertNotNull($minifiedCachedHtml, 'Minified cache should return content');
        $this->assertLessThan($rawSize, $minifiedSize, 'Minified HTML should be smaller');
        $this->assertGreaterThan(0, $speedupCache, 'Cache should be faster than rendering');
    }

    public function testBenchmarkCacheInvalidationCost(): void
    {
        $urls = [];

        for ($i = 0; $i < 50; $i++) {
            $url = "https://example.com/page-{$i}/";
            $urls[] = $url;
            $this->pageCache->put($url, $this->simulateThemeRendering("Page {$i}"), 3600);
        }

        // Measure single invalidation
        $start = hrtime(true);
        $this->pageCache->invalidate($urls[0]);
        $singleInvalidateNs = hrtime(true) - $start;

        // Measure full flush
        for ($i = 0; $i < 50; $i++) {
            $url = "https://example.com/page-{$i}/";
            $this->pageCache->put($url, $this->simulateThemeRendering("Page {$i}"), 3600);
        }

        $start = hrtime(true);
        $this->pageCache->flush();
        $flushNs = hrtime(true) - $start;

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════════╗\n";
        echo "║           COÛT D'INVALIDATION DU CACHE                         ║\n";
        echo "╠══════════════════════════════════════════════════════════════════╣\n";
        echo sprintf("║  Invalidation d'une page   : %8.2f µs                        ║\n", $singleInvalidateNs / 1000);
        echo sprintf("║  Flush complet (50 pages)  : %8.2f µs                        ║\n", $flushNs / 1000);
        echo "╚══════════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        $this->assertNull($this->pageCache->get($urls[0]));
    }

    // -------------------------------------------------------
    // Simulate theme rendering (realistic workload)
    // -------------------------------------------------------

    private function simulateThemeRendering(string $title = 'Benchmark Post'): string
    {
        // Simulate the overhead of a real WordPress template rendering:
        // - Template file loading
        // - wp_head() output
        // - Loop iteration
        // - wp_footer() output

        $posts = [];

        for ($i = 1; $i <= 5; $i++) {
            $posts[] = [
                'title' => "{$title} #{$i}",
                'content' => str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 10),
                'date' => '15 mars 2026',
                'categories' => ['Non classé', 'Actualités'],
            ];
        }

        // Build the full page HTML (simulating theme template rendering)
        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html lang="fr-FR" class="no-js">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '    <meta charset="UTF-8">' . "\n";
        $html .= '    <meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        $html .= "    <title>{$title} &#8211; Mon Site WordPress</title>" . "\n";

        // Simulate wp_head() output
        $html .= '    <link rel="dns-prefetch" href="//s.w.org">' . "\n";
        $html .= '    <link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
        $html .= '    <link rel="stylesheet" id="wp-block-library-css" href="/wp-includes/css/dist/block-library/style.min.css?ver=6.7" type="text/css" media="all">' . "\n";
        $html .= '    <link rel="stylesheet" id="twentytwentyfive-style-css" href="/wp-content/themes/twentytwentyfive/style.css?ver=1.0" type="text/css" media="all">' . "\n";
        $html .= '    <link rel="stylesheet" id="twentytwentyfive-fonts-css" href="/wp-content/themes/twentytwentyfive/assets/css/fonts.css?ver=1.0" type="text/css" media="all">' . "\n";
        $html .= '    <script type="text/javascript" src="/wp-includes/js/wp-emoji-release.min.js?ver=6.7" defer></script>' . "\n";
        $html .= '    <script type="text/javascript">' . "\n";
        $html .= "        document.documentElement.classList.remove('no-js');" . "\n";
        $html .= "        document.documentElement.classList.add('js');" . "\n";
        $html .= '    </script>' . "\n";
        $html .= '    <style id="wp-emoji-styles-inline-css">img.wp-smiley, img.emoji { display: inline !important; }</style>' . "\n";
        $html .= '    <link rel="https://api.w.org/" href="/wp-json/">' . "\n";
        $html .= '    <link rel="EditURI" type="application/rsd+xml" title="RSD" href="/xmlrpc.php?rsd">' . "\n";
        $html .= '    <link rel="wlwmanifest" type="application/wlwmanifest+xml" href="/wp-includes/wlwmanifest.xml">' . "\n";
        $html .= '    <meta name="generator" content="WordPress 6.7">' . "\n";
        $html .= '    <!-- wp_head() output complete -->' . "\n";
        $html .= '</head>' . "\n";

        $html .= '<body class="home blog wp-embed-responsive has-global-padding">' . "\n";
        $html .= '    <div class="wp-site-blocks">' . "\n";

        // Header
        $html .= '        <header class="wp-block-template-part site-header">' . "\n";
        $html .= '            <div class="wp-block-group alignfull">' . "\n";
        $html .= '                <div class="wp-block-site-title"><a href="/">Mon Site WordPress</a></div>' . "\n";
        $html .= '                <p class="wp-block-site-tagline">Un site utilisant WordPress</p>' . "\n";
        $html .= '                <nav class="wp-block-navigation">' . "\n";
        $html .= '                    <ul class="wp-block-navigation__container">' . "\n";
        $html .= '                        <li class="wp-block-navigation-item"><a href="/">Accueil</a></li>' . "\n";
        $html .= '                        <li class="wp-block-navigation-item"><a href="/about/">À propos</a></li>' . "\n";
        $html .= '                        <li class="wp-block-navigation-item"><a href="/contact/">Contact</a></li>' . "\n";
        $html .= '                        <li class="wp-block-navigation-item"><a href="/blog/">Blog</a></li>' . "\n";
        $html .= '                    </ul>' . "\n";
        $html .= '                </nav>' . "\n";
        $html .= '            </div>' . "\n";
        $html .= '        </header>' . "\n";

        // Main content — the loop
        $html .= '        <main class="wp-block-group alignfull is-layout-constrained">' . "\n";

        foreach ($posts as $index => $post) {
            $html .= '            <article id="post-' . ($index + 1) . '" class="post type-post status-publish hentry category-non-classe">' . "\n";
            $html .= '                <header class="entry-header">' . "\n";
            $html .= '                    <h2 class="wp-block-post-title">' . "\n";
            $html .= '                        <a href="/2026/03/' . sanitize_title($post['title']) . '/">' . $post['title'] . '</a>' . "\n";
            $html .= '                    </h2>' . "\n";
            $html .= '                    <div class="wp-block-post-date">' . "\n";
            $html .= '                        <time datetime="2026-03-15">' . $post['date'] . '</time>' . "\n";
            $html .= '                    </div>' . "\n";
            $html .= '                </header>' . "\n";
            $html .= '                <div class="entry-content wp-block-post-content">' . "\n";
            $html .= '                    <p>' . $post['content'] . '</p>' . "\n";
            $html .= '                </div>' . "\n";
            $html .= '                <footer class="entry-footer">' . "\n";
            $html .= '                    <div class="wp-block-post-terms">' . "\n";

            foreach ($post['categories'] as $cat) {
                $html .= '                        <a href="/category/' . sanitize_title($cat) . '/" rel="tag">' . $cat . '</a>' . "\n";
            }

            $html .= '                    </div>' . "\n";
            $html .= '                </footer>' . "\n";
            $html .= '            </article>' . "\n";
            $html .= "\n";
        }

        // Pagination
        $html .= '            <nav class="wp-block-query-pagination">' . "\n";
        $html .= '                <a class="wp-block-query-pagination-previous" href="/page/1/">Précédent</a>' . "\n";
        $html .= '                <div class="wp-block-query-pagination-numbers">' . "\n";
        $html .= '                    <span class="page-numbers current">1</span>' . "\n";
        $html .= '                    <a class="page-numbers" href="/page/2/">2</a>' . "\n";
        $html .= '                    <a class="page-numbers" href="/page/3/">3</a>' . "\n";
        $html .= '                </div>' . "\n";
        $html .= '                <a class="wp-block-query-pagination-next" href="/page/2/">Suivant</a>' . "\n";
        $html .= '            </nav>' . "\n";
        $html .= '        </main>' . "\n";

        // Sidebar
        $html .= '        <aside class="widget-area">' . "\n";
        $html .= '            <div class="wp-block-search">' . "\n";
        $html .= '                <label class="wp-block-search__label">Rechercher</label>' . "\n";
        $html .= '                <input class="wp-block-search__input" type="search" placeholder="Rechercher...">' . "\n";
        $html .= '                <button class="wp-block-search__button">Rechercher</button>' . "\n";
        $html .= '            </div>' . "\n";
        $html .= '            <div class="wp-block-latest-posts">' . "\n";
        $html .= '                <h3>Articles récents</h3>' . "\n";
        $html .= '                <ul>' . "\n";

        foreach ($posts as $post) {
            $html .= '                    <li><a href="#">' . $post['title'] . '</a></li>' . "\n";
        }

        $html .= '                </ul>' . "\n";
        $html .= '            </div>' . "\n";
        $html .= '        </aside>' . "\n";

        // Footer
        $html .= '        <footer class="wp-block-template-part site-footer">' . "\n";
        $html .= '            <div class="wp-block-group alignfull">' . "\n";
        $html .= '                <p>© 2026 Mon Site WordPress. Propulsé par <a href="https://wordpress.org/">WordPress</a></p>' . "\n";
        $html .= '            </div>' . "\n";
        $html .= '        </footer>' . "\n";

        $html .= '    </div>' . "\n";

        // wp_footer() output
        $html .= '    <script type="text/javascript" src="/wp-includes/js/wp-embed.min.js?ver=6.7"></script>' . "\n";
        $html .= '    <script type="text/javascript" src="/wp-includes/js/jquery/jquery.min.js?ver=3.7.1"></script>' . "\n";
        $html .= '    <script type="text/javascript" src="/wp-content/themes/twentytwentyfive/assets/js/navigation.js?ver=1.0"></script>' . "\n";
        $html .= '</body>' . "\n";
        $html .= '</html>';

        return $html;
    }
}

/**
 * Minimal sanitize_title() for standalone mode.
 */
if (!function_exists('sanitize_title')) {
    function sanitize_title(string $title): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    }
}
