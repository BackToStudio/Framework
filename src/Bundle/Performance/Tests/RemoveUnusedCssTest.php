<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Bundle\Performance\Hooks\RemoveUnusedCss;
use PHPUnit\Framework\TestCase;

class RemoveUnusedCssTest extends TestCase
{
    public function testRemovesUnusedClassRule(): void
    {
        $html = '<style>.used { color: red; } .unused { color: blue; }</style><div class="used">content</div>';
        $result = RemoveUnusedCss::process($html, []);

        $this->assertStringContainsString('.used', $result);
        $this->assertStringNotContainsString('.unused', $result);
    }

    public function testKeepsUsedIdRule(): void
    {
        $html = '<style>#main { padding: 10px; } #sidebar { width: 200px; }</style><div id="main">content</div>';
        $result = RemoveUnusedCss::process($html, []);

        $this->assertStringContainsString('#main', $result);
        $this->assertStringNotContainsString('#sidebar', $result);
    }

    public function testKeepsUsedTagRule(): void
    {
        $html = '<style>p { margin: 0; } table { border: 1px; }</style><p>paragraph</p>';
        $result = RemoveUnusedCss::process($html, []);

        $this->assertStringContainsString('p {', $result);
        $this->assertStringNotContainsString('table', $result);
    }

    public function testKeepsAtRules(): void
    {
        $html = '<style>@font-face { font-family: Test; src: url(test.woff2); } .unused { color: red; }</style><div>content</div>';
        $result = RemoveUnusedCss::process($html, []);

        $this->assertStringContainsString('@font-face', $result);
        $this->assertStringNotContainsString('.unused', $result);
    }

    public function testKeepsMediaQueries(): void
    {
        $html = '<style>@media (max-width: 600px) { .used { color: red; } }</style><div class="used">content</div>';
        $result = RemoveUnusedCss::process($html, []);

        $this->assertStringContainsString('@media', $result);
    }

    public function testKeepsRootAndUniversalSelectors(): void
    {
        $html = '<style>:root { --color: red; } * { box-sizing: border-box; } body { margin: 0; }</style><div>content</div>';
        $result = RemoveUnusedCss::process($html, []);

        $this->assertStringContainsString(':root', $result);
        $this->assertStringContainsString('*', $result);
        $this->assertStringContainsString('body', $result);
    }

    public function testRemovesEntireEmptyStyleBlock(): void
    {
        $html = '<style>.unused1 { color: red; } .unused2 { color: blue; }</style><div class="used">content</div>';
        $result = RemoveUnusedCss::process($html, []);

        $this->assertStringNotContainsString('<style', $result);
    }

    public function testPreservesStyleBlockById(): void
    {
        $html = '<style id="protected-css">.unused { color: red; }</style><div>content</div>';
        $result = RemoveUnusedCss::process($html, ['protected-css']);

        $this->assertStringContainsString('.unused', $result);
    }

    public function testHandlesCommaSelectors(): void
    {
        $html = '<style>.a, .b { color: red; }</style><div class="a">content</div>';
        $result = RemoveUnusedCss::process($html, []);

        // Rule is kept because .a is used (even though .b isn't)
        $this->assertStringContainsString('.a, .b', $result);
    }

    public function testHandlesMultipleStyleBlocks(): void
    {
        $html = '<style id="block-a">.used-a { color: red; }</style>'
            . '<style id="block-b">.unused-b { color: blue; }</style>'
            . '<div class="used-a">content</div>';
        $result = RemoveUnusedCss::process($html, []);

        $this->assertStringContainsString('.used-a', $result);
        $this->assertStringNotContainsString('.unused-b', $result);
    }

    public function testHandlesNestedBraces(): void
    {
        $html = '<style>@media (min-width: 768px) { .nested { color: red; } }</style><div class="nested">content</div>';
        $result = RemoveUnusedCss::process($html, []);

        $this->assertStringContainsString('@media', $result);
        $this->assertStringContainsString('.nested', $result);
    }

    public function testReturnsUnchangedHtmlWithoutStyleBlocks(): void
    {
        $html = '<div class="test">content</div>';
        $result = RemoveUnusedCss::process($html, []);

        $this->assertSame($html, $result);
    }

    public function testReturnsEmptyStringForEmptyInput(): void
    {
        $this->assertSame('', RemoveUnusedCss::process('', []));
    }

    public function testRealWorldWordPressScenario(): void
    {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <style id="wp-block-post-featured-image-inline-css">
                .wp-block-post-featured-image { margin: 0; }
                .wp-block-post-featured-image img { max-width: 100%; }
                .wp-block-post-featured-image.alignleft { float: left; }
                .wp-block-post-featured-image.alignright { float: right; }
                .wp-block-post-featured-image.aligncenter { text-align: center; }
            </style>
            <style id="wp-emoji-styles-inline-css">
                img.wp-smiley, img.emoji { display: inline !important; }
            </style>
            <style id="wp-block-template-skip-link-inline-css">
                .skip-link { display: none; }
                .skip-link:focus { display: block; position: fixed; }
            </style>
        </head>
        <body>
            <div class="wp-block-post-featured-image">
                <img src="image.jpg" alt="Featured">
            </div>
        </body>
        </html>
        HTML;

        $result = RemoveUnusedCss::process($html, []);

        // wp-block-post-featured-image rules: keep base + img, remove align variants
        $this->assertStringContainsString('.wp-block-post-featured-image', $result);
        $this->assertStringNotContainsString('.alignleft', $result);
        $this->assertStringNotContainsString('.alignright', $result);
        $this->assertStringNotContainsString('.aligncenter', $result);

        // emoji styles: no img.wp-smiley or img.emoji in markup → removed
        $this->assertStringNotContainsString('wp-smiley', $result);

        // skip-link: no .skip-link in markup → removed entirely
        $this->assertStringNotContainsString('skip-link', $result);
    }
}
