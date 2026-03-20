<?php

declare(strict_types=1);

/**
 * Real WordPress Benchmark: Page Cache Performance
 *
 * This script measures actual HTTP response times for a real WordPress
 * installation with the default theme (Twenty Twenty-Five) and SQLite.
 *
 * It compares:
 * 1. WordPress rendering WITHOUT cache (full PHP/DB execution)
 * 2. Serving the same page FROM the file cache
 * 3. HTML minification savings on real theme output
 *
 * Usage: php tests/integration/real-wp-benchmark.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressHtmlOptimizer;
use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressPageCache;

// ── Configuration ──
$wpUrl = getenv('WP_BENCHMARK_URL') ?: 'http://localhost:8787';
$iterations = 50;
$cacheDir = sys_get_temp_dir() . '/btf_real_benchmark_' . uniqid();

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║     BENCHMARK RÉEL — WordPress + Thème par défaut + SQLite     ║\n";
echo "╠══════════════════════════════════════════════════════════════════╣\n";
echo "║                                                                 ║\n";
echo sprintf("║  WordPress URL    : %-44s ║\n", $wpUrl);
echo sprintf("║  Iterations       : %-44d ║\n", $iterations);
echo "║                                                                 ║\n";

// ── Check WordPress is available ──
$testResponse = @file_get_contents($wpUrl);

if ($testResponse === false) {
    echo "║  ❌ WordPress non accessible à {$wpUrl}\n";
    echo "║  Lancez d'abord: php -S localhost:8787 -t /tmp/wp-benchmark/    ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n";
    exit(1);
}

// Detect theme
preg_match('/themes\/([^\/]+)\//', $testResponse, $themeMatch);
$themeName = $themeMatch[1] ?? 'unknown';
echo sprintf("║  Thème actif      : %-44s ║\n", $themeName);
echo "║                                                                 ║\n";

// ── URLs to benchmark ──
$urls = [
    'Homepage'    => $wpUrl . '/',
    'Single Post' => null, // Will be resolved below
];

// Find a single post URL
preg_match('/<a[^>]+href="([^"]*\?p=\d+[^"]*|[^"]+\/\d{4}\/\d{2}\/[^"]+)"/', $testResponse, $postMatch);
if (!empty($postMatch[1])) {
    $urls['Single Post'] = $postMatch[1];
} else {
    // Try to find any internal link to a post
    preg_match_all('/<a[^>]+href="(' . preg_quote($wpUrl, '/') . '[^"]+)"/', $testResponse, $allLinks);
    foreach ($allLinks[1] ?? [] as $link) {
        if ($link !== $wpUrl . '/' && !str_contains($link, 'feed') && !str_contains($link, 'wp-')) {
            $urls['Single Post'] = $link;
            break;
        }
    }
}

if ($urls['Single Post'] === null) {
    unset($urls['Single Post']);
}

// ── Initialize cache ──
$pageCache = new WordPressPageCache($cacheDir);
$optimizer = new WordPressHtmlOptimizer();

echo "╠══════════════════════════════════════════════════════════════════╣\n";
echo "║  RÉSULTATS                                                     ║\n";
echo "╠══════════════════════════════════════════════════════════════════╣\n";

foreach ($urls as $label => $url) {
    echo "║                                                                 ║\n";
    echo sprintf("║  ── %s (%s)\n", $label, $url);
    echo "║                                                                 ║\n";

    // ── 1. WITHOUT CACHE: WordPress renders via HTTP ──
    $renderTimes = [];
    $html = '';

    // Warmup (1 request to prime opcache etc)
    file_get_contents($url);

    for ($i = 0; $i < $iterations; $i++) {
        $start = hrtime(true);
        $html = file_get_contents($url);
        $renderTimes[] = hrtime(true) - $start;
    }

    $avgRenderUs = array_sum($renderTimes) / count($renderTimes) / 1000;
    $minRenderUs = min($renderTimes) / 1000;
    $maxRenderUs = max($renderTimes) / 1000;
    $p50Render = percentile($renderTimes, 50) / 1000;
    $p95Render = percentile($renderTimes, 95) / 1000;
    $p99Render = percentile($renderTimes, 99) / 1000;

    // ── 2. Store in page cache ──
    $pageCache->put($url, $html, 3600);

    // ── 3. WITH CACHE: Serve from file ──
    $cacheTimes = [];
    $cachedHtml = '';

    for ($i = 0; $i < $iterations; $i++) {
        $start = hrtime(true);
        $cachedHtml = $pageCache->get($url);
        $cacheTimes[] = hrtime(true) - $start;
    }

    $avgCacheUs = array_sum($cacheTimes) / count($cacheTimes) / 1000;
    $minCacheUs = min($cacheTimes) / 1000;
    $maxCacheUs = max($cacheTimes) / 1000;
    $p50Cache = percentile($cacheTimes, 50) / 1000;
    $p95Cache = percentile($cacheTimes, 95) / 1000;
    $p99Cache = percentile($cacheTimes, 99) / 1000;

    // ── 4. Minify ──
    $minifyStart = hrtime(true);
    $minified = $optimizer->optimize($html);
    $minifyTimeUs = (hrtime(true) - $minifyStart) / 1000;

    // ── 5. Minified + cached ──
    $urlMin = $url . '#minified';
    $pageCache->put($urlMin, $minified, 3600);
    $minifiedCacheTimes = [];

    for ($i = 0; $i < $iterations; $i++) {
        $start = hrtime(true);
        $pageCache->get($urlMin);
        $minifiedCacheTimes[] = hrtime(true) - $start;
    }

    $avgMinCacheUs = array_sum($minifiedCacheTimes) / count($minifiedCacheTimes) / 1000;

    // ── Compute metrics ──
    $rawSize = strlen($html);
    $minifiedSize = strlen($minified);
    $savedSize = $rawSize - $minifiedSize;
    $savedPct = round(($savedSize / $rawSize) * 100, 1);
    $speedup = $avgRenderUs / max($avgCacheUs, 0.01);

    // ── Display ──
    echo sprintf("║  ❌ SANS CACHE (WordPress complet via HTTP)                      ║\n");
    echo sprintf("║     Moyenne : %10.0f µs (%7.1f ms)                          ║\n", $avgRenderUs, $avgRenderUs / 1000);
    echo sprintf("║     Min     : %10.0f µs   Max: %10.0f µs                  ║\n", $minRenderUs, $maxRenderUs);
    echo sprintf("║     p50     : %10.0f µs   p95: %10.0f µs   p99: %8.0f µs ║\n", $p50Render, $p95Render, $p99Render);
    echo "║                                                                 ║\n";
    echo sprintf("║  ✅ AVEC CACHE (lecture fichier)                                 ║\n");
    echo sprintf("║     Moyenne : %10.0f µs (%7.3f ms)                          ║\n", $avgCacheUs, $avgCacheUs / 1000);
    echo sprintf("║     Min     : %10.0f µs   Max: %10.0f µs                  ║\n", $minCacheUs, $maxCacheUs);
    echo sprintf("║     p50     : %10.0f µs   p95: %10.0f µs   p99: %8.0f µs ║\n", $p50Cache, $p95Cache, $p99Cache);
    echo "║                                                                 ║\n";
    echo sprintf("║  ⚡ SPEEDUP : ×%.0f plus rapide avec cache                       ║\n", $speedup);
    echo "║                                                                 ║\n";
    echo sprintf("║  📦 TAILLE                                                      ║\n");
    echo sprintf("║     HTML brut   : %7d octets (%5.1f KB)                     ║\n", $rawSize, $rawSize / 1024);
    echo sprintf("║     Minifié     : %7d octets (%5.1f KB)  → -%s%%           ║\n", $minifiedSize, $minifiedSize / 1024, $savedPct);
    echo sprintf("║     Économie    : %7d octets (%5.1f KB)                     ║\n", $savedSize, $savedSize / 1024);
    echo sprintf("║     Minification: %7.0f µs (coût unique)                      ║\n", $minifyTimeUs);
    echo "║                                                                 ║\n";

    // ── Content analysis ──
    $hasEmoji = str_contains($html, 'wp-emoji') || str_contains($html, 'wpemojiSettings');
    $hasGenerator = str_contains($html, '<meta name="generator"');
    $hasRsd = str_contains($html, 'EditURI');
    $hasWlw = str_contains($html, 'wlwmanifest');
    $hasEmbed = str_contains($html, 'wp-embed');
    $hasTypeJs = str_contains($html, 'type="text/javascript"');
    $commentCount = preg_match_all('/<!--(?!\[if\s).*?-->/s', $html);

    echo sprintf("║  🔍 ANALYSE DU HTML (éléments que Performance optimise)          ║\n");
    echo sprintf("║     wp-emoji script    : %s                                   ║\n", $hasEmoji ? 'PRÉSENT ← CleanHead le retire' : 'absent');
    echo sprintf("║     <meta generator>   : %s                                   ║\n", $hasGenerator ? 'PRÉSENT ← CleanHead le retire' : 'absent');
    echo sprintf("║     RSD link           : %s                                   ║\n", $hasRsd ? 'PRÉSENT ← CleanHead le retire' : 'absent');
    echo sprintf("║     wlwmanifest        : %s                                   ║\n", $hasWlw ? 'PRÉSENT ← CleanHead le retire' : 'absent');
    echo sprintf("║     wp-embed           : %s                                   ║\n", $hasEmbed ? 'PRÉSENT ← DisableEmbeds le retire' : 'absent');
    echo sprintf("║     type=\"text/js\"     : %s                                   ║\n", $hasTypeJs ? 'PRÉSENT ← Minification le retire' : 'absent');
    echo sprintf("║     Commentaires HTML  : %d ← Minification les retire          ║\n", $commentCount);
    echo "║                                                                 ║\n";
}

// ── Cache invalidation benchmark ──
echo "╠══════════════════════════════════════════════════════════════════╣\n";
echo "║  COÛT D'INVALIDATION                                          ║\n";
echo "╠══════════════════════════════════════════════════════════════════╣\n";

$start = hrtime(true);
$pageCache->flush();
$flushUs = (hrtime(true) - $start) / 1000;

echo sprintf("║  Flush complet    : %8.0f µs                                  ║\n", $flushUs);
echo "║                                                                 ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "\n";

if (is_dir($cacheDir)) {
    rmdir($cacheDir);
}

// ── Helper ──

function percentile(array $data, int $pct): float
{
    sort($data);
    $index = (int) ceil(count($data) * $pct / 100) - 1;

    return $data[max(0, $index)];
}
