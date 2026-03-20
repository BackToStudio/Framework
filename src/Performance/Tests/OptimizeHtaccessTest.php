<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Cache\Contracts\TransientStoreInterface;
use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Performance\Hooks\OptimizeHtaccess;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass to control .htaccess path and marker writing.
 */
class TestableOptimizeHtaccess extends OptimizeHtaccess
{
    private ?string $htaccessPath = null;

    /** @var array<string, array{marker: string, lines: string[]}> */
    private array $markerCalls = [];

    public function setHtaccessPath(?string $path): void
    {
        $this->htaccessPath = $path;
    }

    /**
     * @return array<string, array{marker: string, lines: string[]}>
     */
    public function getMarkerCalls(): array
    {
        return $this->markerCalls;
    }

    protected function getHtaccessPath(): ?string
    {
        return $this->htaccessPath;
    }

    protected function insertWithMarkers(string $path, string $marker, array $lines): bool
    {
        $this->markerCalls[$path] = ['marker' => $marker, 'lines' => $lines];

        return true;
    }
}

class OptimizeHtaccessTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private TransientStoreInterface $transientStore;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->transientStore = $this->createMock(TransientStoreInterface::class);
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

        $hook = new TestableOptimizeHtaccess($this->hookDispatcher, $this->transientStore);
        $hook->setHtaccessPath('/var/www/html/.htaccess');

        $hook->applyDirectives();

        $calls = $hook->getMarkerCalls();
        $this->assertArrayHasKey('/var/www/html/.htaccess', $calls);
        $this->assertSame('BackTo Performance', $calls['/var/www/html/.htaccess']['marker']);
        $this->assertNotEmpty($calls['/var/www/html/.htaccess']['lines']);
    }

    public function testApplyDirectivesSkipsNullPath(): void
    {
        $hook = new TestableOptimizeHtaccess($this->hookDispatcher, $this->transientStore);
        $hook->setHtaccessPath(null);

        $hook->applyDirectives();

        $this->assertEmpty($hook->getMarkerCalls());
    }

    public function testRemoveDirectivesWritesEmptyLines(): void
    {
        $hook = new TestableOptimizeHtaccess($this->hookDispatcher, $this->transientStore);
        $hook->setHtaccessPath('/var/www/html/.htaccess');

        $hook->removeDirectives();

        $calls = $hook->getMarkerCalls();
        $this->assertArrayHasKey('/var/www/html/.htaccess', $calls);
        $this->assertSame([], $calls['/var/www/html/.htaccess']['lines']);
    }

    public function testRemoveDirectivesSkipsNullPath(): void
    {
        $hook = new TestableOptimizeHtaccess($this->hookDispatcher, $this->transientStore);
        $hook->setHtaccessPath(null);

        $hook->removeDirectives();

        $this->assertEmpty($hook->getMarkerCalls());
    }

    public function testActivateCallsApplyDirectives(): void
    {
        $this->transientStore->method('get')->willReturn(null);

        $hook = new TestableOptimizeHtaccess($this->hookDispatcher, $this->transientStore);
        $hook->setHtaccessPath('/var/www/html/.htaccess');

        $hook->activate();

        $calls = $hook->getMarkerCalls();
        $this->assertArrayHasKey('/var/www/html/.htaccess', $calls);
        $this->assertNotEmpty($calls['/var/www/html/.htaccess']['lines']);
    }

    public function testApplyDirectivesAllDisabledSkipsWrite(): void
    {
        $hook = new TestableOptimizeHtaccess(
            $this->hookDispatcher,
            $this->transientStore,
            gzip: false,
            browserCache: false,
            removeEtags: false,
            keepAlive: false
        );
        $hook->setHtaccessPath('/var/www/html/.htaccess');

        $hook->applyDirectives();

        // No lines to write -> no call
        $this->assertEmpty($hook->getMarkerCalls());
    }

    public function testApplyDirectivesSkipsRedundantWrite(): void
    {
        $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
        $lines = $hook->buildDirectives();
        $expectedHash = md5(implode("\n", $lines));

        // TransientStore returns the same hash -> skip write
        $this->transientStore->method('get')->willReturn($expectedHash);

        $testable = new TestableOptimizeHtaccess($this->hookDispatcher, $this->transientStore);
        $testable->setHtaccessPath('/var/www/html/.htaccess');
        $testable->applyDirectives();

        $this->assertEmpty($testable->getMarkerCalls());
    }

    public function testApplyDirectivesStoresHashAfterWrite(): void
    {
        $this->transientStore->method('get')->willReturn(null);
        $this->transientStore->expects($this->once())
            ->method('set')
            ->with('backto_htaccess_hash', $this->isType('string'), 86400);

        $hook = new TestableOptimizeHtaccess($this->hookDispatcher, $this->transientStore);
        $hook->setHtaccessPath('/var/www/html/.htaccess');

        $hook->applyDirectives();
    }

    public function testDefaultStaticTtlIsOneYear(): void
    {
        $hook = new OptimizeHtaccess($this->hookDispatcher, $this->transientStore);
        $content = implode("\n", $hook->buildDirectives());

        $this->assertStringContainsString('31536000', $content);
    }
}
