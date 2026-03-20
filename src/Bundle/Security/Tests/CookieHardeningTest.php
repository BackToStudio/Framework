<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\CookieHardening;
use PHPUnit\Framework\TestCase;

class TestableCookieHardening extends CookieHardening
{
    /** @var array<string, array{value: string, options: array<string, mixed>}> */
    public array $cookies = [];

    public bool $simulateHeadersSent = false;

    /** @var array{lifetime: int, path: string, domain: string, secure: bool, httponly: bool, samesite: 'Strict'|'Lax'|'None'} */
    private array $sessionParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false,
        'samesite' => 'Lax',
    ];

    /** @var array{lifetime: int, path: string, domain: string, secure: bool, httponly: bool, samesite: 'Strict'|'Lax'|'None'}|null */
    public ?array $updatedSessionParams = null;

    protected function setHardenedCookie(string $name, string $value, int $expire): void
    {
        if ($this->simulateHeadersSent) {
            return;
        }

        $this->cookies[$name] = [
            'value' => $value,
            'options' => $this->buildCookieOptions($expire),
        ];
    }

    protected function headersSent(): bool
    {
        return $this->simulateHeadersSent;
    }

    protected function getAuthCookieName(string $scheme): string
    {
        return $scheme === 'secure_auth' ? 'wordpress_sec_test' : 'wordpress_test';
    }

    protected function getLoggedInCookieName(): string
    {
        return 'wordpress_logged_in_test';
    }

    protected function getCookiePath(): string
    {
        return '/';
    }

    protected function getCookieDomain(): string
    {
        return 'example.com';
    }

    protected function getSessionCookieParams(): array
    {
        return $this->sessionParams;
    }

    protected function setSessionCookieParams(array $params): void
    {
        $this->updatedSessionParams = $params;
    }
}

class CookieHardeningTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private TestableCookieHardening $hardening;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->hardening = new TestableCookieHardening($this->dispatcher);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->hardening);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->hardening);
    }

    public function testGetName(): void
    {
        $this->assertSame('cookie_hardening', $this->hardening->getName());
    }

    public function testHooksRegistersFiltersAndActions(): void
    {
        $this->dispatcher->expects($this->exactly(2))->method('addFilter');
        $this->dispatcher->expects($this->exactly(3))->method('addAction');

        $this->hardening->hooks();
    }

    public function testDefaultsAreSecure(): void
    {
        $this->assertTrue($this->hardening->isSecure());
        $this->assertTrue($this->hardening->isHttpOnly());
        $this->assertSame('Lax', $this->hardening->getSameSite());
    }

    public function testSetSecure(): void
    {
        $result = $this->hardening->setSecure(false);
        $this->assertFalse($this->hardening->isSecure());
        $this->assertSame($this->hardening, $result);
    }

    public function testSetHttpOnly(): void
    {
        $result = $this->hardening->setHttpOnly(false);
        $this->assertFalse($this->hardening->isHttpOnly());
        $this->assertSame($this->hardening, $result);
    }

    public function testSetSameSiteAcceptsValidValues(): void
    {
        $this->hardening->setSameSite('Strict');
        $this->assertSame('Strict', $this->hardening->getSameSite());

        $this->hardening->setSameSite('None');
        $this->assertSame('None', $this->hardening->getSameSite());

        $this->hardening->setSameSite('Lax');
        $this->assertSame('Lax', $this->hardening->getSameSite());
    }

    public function testSetSameSiteIgnoresInvalidValues(): void
    {
        $this->hardening->setSameSite('Invalid');
        $this->assertSame('Lax', $this->hardening->getSameSite());
    }

    public function testForceSecureReturnsTrueWhenEnabled(): void
    {
        $this->assertTrue($this->hardening->forceSecure(false));
        $this->assertTrue($this->hardening->forceSecure(true));
    }

    public function testForceSecureReturnsTrueWhenOriginalIsTrue(): void
    {
        $this->hardening->setSecure(false);
        $this->assertTrue($this->hardening->forceSecure(true));
    }

    public function testForceSecureReturnsFalseWhenBothDisabled(): void
    {
        $this->hardening->setSecure(false);
        $this->assertFalse($this->hardening->forceSecure(false));
    }

    public function testHardenAuthCookieSetsHardenedCookie(): void
    {
        $this->hardening->hardenAuthCookie('cookie_value', 3600, 3600, 1, 'secure_auth');

        $this->assertArrayHasKey('wordpress_sec_test', $this->hardening->cookies);
        $cookie = $this->hardening->cookies['wordpress_sec_test'];
        $this->assertSame('cookie_value', $cookie['value']);
        $this->assertTrue($cookie['options']['secure']);
        $this->assertTrue($cookie['options']['httponly']);
        $this->assertSame('Lax', $cookie['options']['samesite']);
    }

    public function testHardenAuthCookieWithNonSecureScheme(): void
    {
        $this->hardening->hardenAuthCookie('value', 3600, 3600, 1, 'auth');

        $this->assertArrayHasKey('wordpress_test', $this->hardening->cookies);
    }

    public function testHardenLoggedInCookie(): void
    {
        $this->hardening->hardenLoggedInCookie('cookie_value', 3600, 3600, 1, 'logged_in');

        $this->assertArrayHasKey('wordpress_logged_in_test', $this->hardening->cookies);
        $cookie = $this->hardening->cookies['wordpress_logged_in_test'];
        $this->assertSame('cookie_value', $cookie['value']);
        $this->assertTrue($cookie['options']['secure']);
    }

    public function testHeadersSentPreventsSettingCookies(): void
    {
        $this->hardening->simulateHeadersSent = true;
        $this->hardening->hardenAuthCookie('value', 3600, 3600, 1, 'secure_auth');

        $this->assertEmpty($this->hardening->cookies);
    }

    public function testBuildCookieOptions(): void
    {
        $options = $this->hardening->buildCookieOptions(7200);

        $this->assertSame(7200, $options['expires']);
        $this->assertSame('/', $options['path']);
        $this->assertSame('example.com', $options['domain']);
        $this->assertTrue($options['secure']);
        $this->assertTrue($options['httponly']);
        $this->assertSame('Lax', $options['samesite']);
    }

    public function testConfigureSessionCookie(): void
    {
        $this->hardening->configureSessionCookie();

        $this->assertNotNull($this->hardening->updatedSessionParams);
        $this->assertTrue($this->hardening->updatedSessionParams['secure']);
        $this->assertTrue($this->hardening->updatedSessionParams['httponly']);
        $this->assertSame('Lax', $this->hardening->updatedSessionParams['samesite']);
    }

    public function testConfigureSessionCookieRespectsCustomSettings(): void
    {
        $this->hardening->setSecure(false)->setHttpOnly(false)->setSameSite('Strict');
        $this->hardening->configureSessionCookie();

        $this->assertFalse($this->hardening->updatedSessionParams['secure']);
        $this->assertFalse($this->hardening->updatedSessionParams['httponly']);
        $this->assertSame('Strict', $this->hardening->updatedSessionParams['samesite']);
    }

    public function testFluentInterface(): void
    {
        $result = $this->hardening
            ->setSecure(true)
            ->setHttpOnly(true)
            ->setSameSite('Strict');

        $this->assertSame($this->hardening, $result);
    }
}
