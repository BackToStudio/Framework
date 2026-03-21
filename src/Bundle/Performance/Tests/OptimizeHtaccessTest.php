<?php

declare(strict_types=1);

/**
 * Stub WordPress functions in the global namespace so OptimizeHtaccess
 * can be tested without a running WordPress installation.
 */

namespace {
    if (!function_exists('get_home_path')) {
        function get_home_path(): string
        {
            return OptimizeHtaccessTestState::$homePath;
        }
    }

    if (!function_exists('insert_with_markers')) {
        /**
         * @param string[] $lines
         */
        function insert_with_markers(string $path, string $marker, array $lines): bool
        {
            OptimizeHtaccessTestState::$markerCalls[$path] = ['marker' => $marker, 'lines' => $lines];
            return true;
        }
    }

    /**
     * Shared mutable state for the WP function stubs above.
     */
    class OptimizeHtaccessTestState
    {
        public static string $homePath = '';
        /** @var array<string, array{marker: string, lines: string[]}> */
        public static array $markerCalls = [];

        public static function reset(): void
        {
            self::$homePath = '';
            self::$markerCalls = [];
        }
    }
}

namespace BackTo\Framework\Bundle\Performance\Tests {

    use BackTo\Framework\Cache\Contracts\CacheStoreInterface;
    use BackTo\Framework\Contracts\ActivationHooks;
    use BackTo\Framework\Contracts\HookDispatcherInterface;
    use BackTo\Framework\Contracts\Hooks;
    use BackTo\Framework\Bundle\Performance\Hooks\Server\OptimizeHtaccess;
    use OptimizeHtaccessTestState;
    use PHPUnit\Framework\TestCase;

    class OptimizeHtaccessTest extends TestCase
    {
        private HookDispatcherInterface $hookDispatcher;
        private CacheStoreInterface $transientStore;

        protected function setUp(): void
        {
            $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
            $this->transientStore = $this->createMock(CacheStoreInterface::class);
            OptimizeHtaccessTestState::reset();
        }

        protected function tearDown(): void
        {
            OptimizeHtaccessTestState::reset();
        }

        public function testImplementsRequiredInterfaces(): void
        {
            $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);

            $this->assertInstanceOf(Hooks::class, $hook);
            $this->assertInstanceOf(ActivationHooks::class, $hook);
        }

        public function testMarkerConstant(): void
        {
            $this->assertSame('BackTo Performance', OptimizeHtaccess::MARKER);
        }

        public function testHooksRegistersAdminInit(): void
        {
            $this->hookDispatcher->expects($this->once())
                ->method('addAction')
                ->with('admin_init', $this->anything());

            $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
            $hook->hooks();
        }

        public function testBuildDirectivesIncludesAllSectionsbyDefault(): void
        {
            $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
            $lines = $hook->buildDirectives();
            $content = implode("\n", $lines);

            $this->assertStringContainsString('mod_deflate', $content);
            $this->assertStringContainsString('mod_expires', $content);
            $this->assertStringContainsString('ETag', $content);
            $this->assertStringContainsString('keep-alive', $content);
        }

        public function testBuildDirectivesGzipOnly(): void
        {
            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: true,
                browserCache: false,
                removeEtags: false,
                keepAlive: false
            );
            $lines = $hook->buildDirectives();
            $content = implode("\n", $lines);

            $this->assertStringContainsString('mod_deflate', $content);
            $this->assertStringNotContainsString('mod_expires', $content);
            $this->assertStringNotContainsString('ETag', $content);
            $this->assertStringNotContainsString('keep-alive', $content);
        }

        public function testBuildDirectivesBrowserCacheOnly(): void
        {
            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: false,
                browserCache: true,
                removeEtags: false,
                keepAlive: false
            );
            $lines = $hook->buildDirectives();
            $content = implode("\n", $lines);

            $this->assertStringNotContainsString('mod_deflate', $content);
            $this->assertStringContainsString('mod_expires', $content);
            $this->assertStringContainsString('Cache-Control', $content);
        }

        public function testBuildDirectivesEtagOnly(): void
        {
            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: false,
                browserCache: false,
                removeEtags: true,
                keepAlive: false
            );
            $lines = $hook->buildDirectives();
            $content = implode("\n", $lines);

            $this->assertStringContainsString('ETag', $content);
            $this->assertStringContainsString('FileETag None', $content);
        }

        public function testBuildDirectivesKeepAliveOnly(): void
        {
            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: false,
                browserCache: false,
                removeEtags: false,
                keepAlive: true
            );
            $lines = $hook->buildDirectives();
            $content = implode("\n", $lines);

            $this->assertStringContainsString('keep-alive', $content);
            $this->assertStringNotContainsString('mod_deflate', $content);
        }

        public function testBuildDirectivesAllDisabledReturnsEmpty(): void
        {
            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: false,
                browserCache: false,
                removeEtags: false,
                keepAlive: false
            );

            $this->assertSame([], $hook->buildDirectives());
        }

        public function testGzipDirectivesIncludeAllMimeTypes(): void
        {
            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: true,
                browserCache: false,
                removeEtags: false,
                keepAlive: false
            );
            $content = implode("\n", $hook->buildDirectives());

            $this->assertStringContainsString('text/html', $content);
            $this->assertStringContainsString('text/css', $content);
            $this->assertStringContainsString('application/javascript', $content);
            $this->assertStringContainsString('application/json', $content);
            $this->assertStringContainsString('application/ld+json', $content);
            $this->assertStringContainsString('image/svg+xml', $content);
            $this->assertStringContainsString('font/ttf', $content);
            $this->assertStringContainsString('font/otf', $content);
        }

        public function testGzipExcludesAlreadyCompressedFormats(): void
        {
            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: true,
                browserCache: false,
                removeEtags: false,
                keepAlive: false
            );
            $content = implode("\n", $hook->buildDirectives());

            $this->assertStringContainsString('no-gzip', $content);
            $this->assertStringContainsString('woff2', $content);
        }

        public function testBrowserCacheUsesCustomTtl(): void
        {
            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: false,
                browserCache: true,
                removeEtags: false,
                keepAlive: false,
                staticTtl: 86400
            );
            $content = implode("\n", $hook->buildDirectives());

            $this->assertStringContainsString('access plus 86400 seconds', $content);
            $this->assertStringContainsString('max-age=86400', $content);
            $this->assertStringNotContainsString('31536000', $content);
        }

        public function testBrowserCacheHtmlNotCached(): void
        {
            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: false,
                browserCache: true,
                removeEtags: false,
                keepAlive: false
            );
            $content = implode("\n", $hook->buildDirectives());

            $this->assertStringContainsString('text/html "access plus 0 seconds"', $content);
            $this->assertStringContainsString('no-cache, no-store, must-revalidate', $content);
        }

        public function testBrowserCacheIncludesModernFormats(): void
        {
            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: false,
                browserCache: true,
                removeEtags: false,
                keepAlive: false
            );
            $content = implode("\n", $hook->buildDirectives());

            $this->assertStringContainsString('image/webp', $content);
            $this->assertStringContainsString('image/avif', $content);
            $this->assertStringContainsString('font/woff2', $content);
            $this->assertStringContainsString('immutable', $content);
        }

        public function testBrowserCacheJsonNotCached(): void
        {
            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: false,
                browserCache: true,
                removeEtags: false,
                keepAlive: false
            );
            $content = implode("\n", $hook->buildDirectives());

            $this->assertStringContainsString('application/json "access plus 0 seconds"', $content);
        }

        public function testApplyDirectivesWritesToHtaccess(): void
        {
            $this->transientStore->method('get')->willReturn(null);

            OptimizeHtaccessTestState::$homePath = '/var/www/html';

            $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
            $hook->applyDirectives();

            $calls = OptimizeHtaccessTestState::$markerCalls;
            $this->assertArrayHasKey('/var/www/html/.htaccess', $calls);
            $this->assertSame('BackTo Performance', $calls['/var/www/html/.htaccess']['marker']);
            $this->assertNotEmpty($calls['/var/www/html/.htaccess']['lines']);
        }

        public function testApplyDirectivesSkipsNullPath(): void
        {
            OptimizeHtaccessTestState::$homePath = '';

            $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
            $hook->applyDirectives();

            $this->assertEmpty(OptimizeHtaccessTestState::$markerCalls);
        }

        public function testRemoveDirectivesWritesEmptyLines(): void
        {
            OptimizeHtaccessTestState::$homePath = '/var/www/html';

            $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
            $hook->removeDirectives();

            $calls = OptimizeHtaccessTestState::$markerCalls;
            $this->assertArrayHasKey('/var/www/html/.htaccess', $calls);
            $this->assertSame([], $calls['/var/www/html/.htaccess']['lines']);
        }

        public function testRemoveDirectivesSkipsNullPath(): void
        {
            OptimizeHtaccessTestState::$homePath = '';

            $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
            $hook->removeDirectives();

            $this->assertEmpty(OptimizeHtaccessTestState::$markerCalls);
        }

        public function testActivateCallsApplyDirectives(): void
        {
            $this->transientStore->method('get')->willReturn(null);

            OptimizeHtaccessTestState::$homePath = '/var/www/html';

            $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
            $hook->activate();

            $calls = OptimizeHtaccessTestState::$markerCalls;
            $this->assertArrayHasKey('/var/www/html/.htaccess', $calls);
            $this->assertNotEmpty($calls['/var/www/html/.htaccess']['lines']);
        }

        public function testApplyDirectivesAllDisabledSkipsWrite(): void
        {
            OptimizeHtaccessTestState::$homePath = '/var/www/html';

            $hook = new OptimizeHtaccess(
                $this->hookDispatcher,
                $this->transientStore,
                gzip: false,
                browserCache: false,
                removeEtags: false,
                keepAlive: false
            );

            $hook->applyDirectives();

            // No lines to write -> no call
            $this->assertEmpty(OptimizeHtaccessTestState::$markerCalls);
        }

        public function testApplyDirectivesSkipsRedundantWrite(): void
        {
            $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
            $lines = $hook->buildDirectives();
            $expectedHash = md5(implode("\n", $lines));

            // TransientStore returns the same hash -> skip write
            $this->transientStore->method('get')->willReturn($expectedHash);

            OptimizeHtaccessTestState::$homePath = '/var/www/html';

            $hook2 = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
            $hook2->applyDirectives();

            $this->assertEmpty(OptimizeHtaccessTestState::$markerCalls);
        }

        public function testApplyDirectivesStoresHashAfterWrite(): void
        {
            $this->transientStore->method('get')->willReturn(null);
            $this->transientStore->expects($this->once())
                ->method('set')
                ->with('backto_htaccess_hash', $this->isType('string'), 86400);

            OptimizeHtaccessTestState::$homePath = '/var/www/html';

            $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
            $hook->applyDirectives();
        }

        public function testDefaultStaticTtlIsOneYear(): void
        {
            $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
            $content = implode("\n", $hook->buildDirectives());

            $this->assertStringContainsString('31536000', $content);
        }
    }
}
