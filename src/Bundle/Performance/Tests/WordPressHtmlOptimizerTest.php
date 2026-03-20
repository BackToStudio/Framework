<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Bundle\Performance\Contracts\HtmlOptimizerInterface;
use BackTo\Framework\Bundle\Performance\Infrastructure\WordPressHtmlOptimizer;
use PHPUnit\Framework\TestCase;

class WordPressHtmlOptimizerTest extends TestCase
{
    private WordPressHtmlOptimizer $optimizer;

    protected function setUp(): void
    {
        $this->optimizer = new WordPressHtmlOptimizer();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(HtmlOptimizerInterface::class, $this->optimizer);
    }

    public function testReturnsEmptyStringForEmptyInput(): void
    {
        $this->assertSame('', $this->optimizer->optimize(''));
    }

    // -------------------------------------------------------
    // HTML comments
    // -------------------------------------------------------

    public function testRemovesHtmlComments(): void
    {
        $html = '<div><!-- This is a comment -->content</div>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringNotContainsString('<!-- This is a comment -->', $result);
        $this->assertStringContainsString('content', $result);
    }

    public function testPreservesIeConditionalComments(): void
    {
        $html = '<!--[if IE 9]><link rel="stylesheet" href="ie9.css"><![endif]-->';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('<!--[if IE 9]>', $result);
    }

    // -------------------------------------------------------
    // Whitespace
    // -------------------------------------------------------

    public function testCollapsesWhitespace(): void
    {
        $html = "<div>   \n\n   content   \n   </div>";
        $result = $this->optimizer->optimize($html);

        $this->assertStringNotContainsString("\n\n", $result);
    }

    public function testCollapsesWhitespaceBetweenBlockTags(): void
    {
        // Block-level tags: whitespace is removed entirely
        $html = '<div>    </div>    <p>text</p>';
        $result = $this->optimizer->optimize($html);

        $this->assertSame('<div> </div><p>text</p>', $result);
    }

    public function testPreservesSpaceBetweenInlineTags(): void
    {
        $html = '<span>hello</span>  <span>world</span>';
        $result = $this->optimizer->optimize($html);

        $this->assertSame('<span>hello</span> <span>world</span>', $result);
    }

    // -------------------------------------------------------
    // type attributes
    // -------------------------------------------------------

    public function testRemovesTypeAttributes(): void
    {
        $html = '<script type="text/javascript" src="app.js"></script>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringNotContainsString('type="text/javascript"', $result);
    }

    // -------------------------------------------------------
    // Preserved content
    // -------------------------------------------------------

    public function testPreservesPreContent(): void
    {
        $html = '<pre>   preserved   whitespace   </pre>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('   preserved   whitespace   ', $result);
    }

    public function testPreservesStringLiteralsInScripts(): void
    {
        $html = '<script>var x = "  hello  ";</script>';
        $result = $this->optimizer->optimize($html);

        // String content within quotes is preserved
        $this->assertStringContainsString('"  hello  "', $result);
    }

    // -------------------------------------------------------
    // CSS minification
    // -------------------------------------------------------

    public function testMinifiesInlineCss(): void
    {
        $html = '<style>.foo {  color: red;  margin: 0px;  }</style>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('.foo{color:red;margin:0}', $result);
    }

    public function testMinifiesCssRemovesComments(): void
    {
        $html = '<style>/* comment */ .bar { display: block; }</style>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringNotContainsString('comment', $result);
        $this->assertStringContainsString('.bar{display:block}', $result);
    }

    public function testMinifiesCssRemovesTrailingSemicolons(): void
    {
        $html = '<style>.x { color: red; font: bold; }</style>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('color:red;font:bold}', $result);
    }

    public function testMinifiesCssShortensHexColors(): void
    {
        $html = '<style>.x { color: #aabbcc; }</style>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('#abc', $result);
    }

    public function testMinifiesCssRemovesLeadingZeros(): void
    {
        $html = '<style>.x { opacity: 0.5; }</style>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('opacity:.5', $result);
    }

    public function testMinifiesMultipleStyleBlocks(): void
    {
        $html = '<style>.a { margin: 0px; }</style><style>.b { padding: 0px; }</style>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('.a{margin:0}', $result);
        $this->assertStringContainsString('.b{padding:0}', $result);
    }

    public function testPreservesCssWithIdAttribute(): void
    {
        $html = '<style id="global-styles-inline-css">.wp-block { margin: 0px auto; }</style>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('id="global-styles-inline-css"', $result);
        $this->assertStringContainsString('.wp-block{margin:0 auto}', $result);
    }

    // -------------------------------------------------------
    // JS minification
    // -------------------------------------------------------

    public function testMinifiesInlineJs(): void
    {
        $html = '<script>var   x  =  1;  var   y  =  2;</script>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('var x=1;var y=2;', $result);
    }

    public function testMinifiesJsRemovesSingleLineComments(): void
    {
        $html = "<script>var x = 1; // this is a comment\nvar y = 2;</script>";
        $result = $this->optimizer->optimize($html);

        $this->assertStringNotContainsString('this is a comment', $result);
        $this->assertStringContainsString('var x=1;', $result);
    }

    public function testMinifiesJsRemovesMultiLineComments(): void
    {
        $html = '<script>/* comment */ var z = 3;</script>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringNotContainsString('comment', $result);
        $this->assertStringContainsString('var z=3;', $result);
    }

    public function testMinifiesJsPreservesKeywords(): void
    {
        $html = '<script>function test() { return true; }</script>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('function test()', $result);
        $this->assertStringContainsString('return true', $result);
    }

    public function testDoesNotMinifyJsonLd(): void
    {
        $html = '<script type="application/ld+json">{"@context":  "https://schema.org"}</script>';
        $result = $this->optimizer->optimize($html);

        // JSON-LD should not be minified (data, not code)
        $this->assertStringContainsString('"@context":  "https://schema.org"', $result);
    }

    public function testDoesNotMinifyImportmap(): void
    {
        $html = '<script type="importmap">{"imports":  {"foo":  "bar"}}</script>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString('"imports":  {"foo":  "bar"}', $result);
    }

    // -------------------------------------------------------
    // Real-world scenarios
    // -------------------------------------------------------

    public function testMinifiesWordPressGlobalStyles(): void
    {
        $css = <<<'CSS'
        body {
            --wp--preset--color--primary: #1e1e1e;
            --wp--preset--spacing--30: 0.44rem;
            margin: 0px;
            padding: 0px;
        }
        .wp-block-group {
            box-sizing: border-box;
        }
        CSS;

        $html = '<style id="global-styles-inline-css">' . $css . '</style>';
        $result = $this->optimizer->optimize($html);

        // Should be significantly smaller
        $this->assertLessThan(strlen($html), strlen($result));
        // Key content preserved
        $this->assertStringContainsString('--wp--preset--color--primary:#1e1e1e', $result);
        $this->assertStringContainsString('margin:0', $result);
    }

    public function testMinifiesEmojiDetectionScript(): void
    {
        $js = <<<'JS'
        window._wpemojiSettings = {"baseUrl":"https:\/\/s.w.org\/images\/core\/emoji\/15.0.3\/72x72\/"};
        /*! This file is auto-generated */
        !function(i,n){var o,s,e;function c(e){try{var t={supportTests:e,timestamp:(new Date).valueOf()};sessionStorage.setItem(o,JSON.stringify(t))}catch(e){}}
        }((window,document),window._wpemojiSettings);
        JS;

        $html = '<script>' . $js . '</script>';
        $result = $this->optimizer->optimize($html);

        $this->assertLessThan(strlen($html), strlen($result));
        $this->assertStringContainsString('wpemojiSettings', $result);
    }

    public function testMinifiesFontFaceDeclarations(): void
    {
        $css = <<<'CSS'
        @font-face {
            font-family: Manrope;
            font-style: normal;
            font-weight: 200 900;
            font-display: fallback;
            src: url('/fonts/manrope.woff2') format('woff2');
        }
        CSS;

        $html = '<style>' . $css . '</style>';
        $result = $this->optimizer->optimize($html);

        $this->assertStringContainsString("@font-face{font-family:Manrope", $result);
        $this->assertStringNotContainsString("\n", $result);
    }
}
