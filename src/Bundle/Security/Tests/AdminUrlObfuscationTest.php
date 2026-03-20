<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\UserContextInterface;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Bundle\Security\Hardening\AdminUrlObfuscation;
use BackTo\Framework\Bundle\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use PHPUnit\Framework\TestCase;

class TestableAdminUrlObfuscation extends AdminUrlObfuscation
{
    private string $requestUri = '';
    private string $clientIp = '127.0.0.1';
    private bool $loggedIn = false;
    public bool $loginPageLoaded = false;
    public bool $sent404 = false;

    public function setRequestUri(string $uri): void
    {
        $this->requestUri = $uri;
    }

    public function setClientIp(string $ip): void
    {
        $this->clientIp = $ip;
    }

    public function setLoggedIn(bool $loggedIn): void
    {
        $this->loggedIn = $loggedIn;
    }

    protected function getRequestUri(): string
    {
        return $this->requestUri;
    }

    protected function getClientIp(): string
    {
        return $this->clientIp;
    }

    protected function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    protected function loadLoginPage(): void
    {
        $this->loginPageLoaded = true;
    }

    protected function send404(): void
    {
        $this->sent404 = true;
    }
}

class AdminUrlObfuscationTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private LoggerInterface $logger;
    private RequestContextInterface $requestContext;
    private ClientIpResolverInterface $ipResolver;
    private TestableAdminUrlObfuscation $obfuscation;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->requestContext->method('getRemoteAddr')->willReturn('127.0.0.1');
        $this->requestContext->method('server')->willReturn('');
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->ipResolver = $this->createMock(ClientIpResolverInterface::class);
        $this->ipResolver->method('getClientIp')->willReturn('127.0.0.1');
        $userContext = $this->createMock(UserContextInterface::class);
        $this->obfuscation = new TestableAdminUrlObfuscation($this->dispatcher, $this->logger, $this->requestContext, $this->ipResolver, $userContext);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->obfuscation);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->obfuscation);
    }

    public function testGetName(): void
    {
        $this->assertSame('admin_url_obfuscation', $this->obfuscation->getName());
    }

    public function testHooksDoNothingWithoutSlug(): void
    {
        $this->dispatcher->expects($this->never())->method('addAction');
        $this->dispatcher->expects($this->never())->method('addFilter');

        $this->obfuscation->hooks();
    }

    public function testHooksRegisterWhenSlugIsSet(): void
    {
        $this->obfuscation->setLoginSlug('secret-login');

        $this->dispatcher->expects($this->exactly(2))->method('addAction');
        $this->dispatcher->expects($this->exactly(3))->method('addFilter');

        $this->obfuscation->hooks();
    }

    public function testSetAndGetLoginSlug(): void
    {
        $result = $this->obfuscation->setLoginSlug('my-login');
        $this->assertSame('my-login', $this->obfuscation->getLoginSlug());
        $this->assertSame($this->obfuscation, $result);
    }

    public function testSetLoginSlugTrimsSlashes(): void
    {
        $this->obfuscation->setLoginSlug('/my-login/');
        $this->assertSame('my-login', $this->obfuscation->getLoginSlug());
    }

    public function testHandleCustomLoginSlugLoadsLoginPage(): void
    {
        $this->obfuscation->setLoginSlug('secret-login');
        $this->obfuscation->setRequestUri('/secret-login');

        $this->obfuscation->handleCustomLoginSlug();

        $this->assertTrue($this->obfuscation->loginPageLoaded);
    }

    public function testHandleCustomLoginSlugIgnoresOtherPaths(): void
    {
        $this->obfuscation->setLoginSlug('secret-login');
        $this->obfuscation->setRequestUri('/some-other-page');

        $this->obfuscation->handleCustomLoginSlug();

        $this->assertFalse($this->obfuscation->loginPageLoaded);
    }

    public function testBlockDefaultLoginSends404ForWpLogin(): void
    {
        $this->obfuscation->setLoginSlug('secret-login');
        $this->obfuscation->setRequestUri('/wp-login.php');
        $this->obfuscation->setLoggedIn(false);

        $this->logger->expects($this->once())->method('warning');

        $this->obfuscation->blockDefaultLogin();

        $this->assertTrue($this->obfuscation->sent404);
    }

    public function testBlockDefaultLoginAllowsLoggedInUsers(): void
    {
        $this->obfuscation->setLoginSlug('secret-login');
        $this->obfuscation->setRequestUri('/wp-login.php');
        $this->obfuscation->setLoggedIn(true);

        $this->obfuscation->blockDefaultLogin();

        $this->assertFalse($this->obfuscation->sent404);
    }

    public function testBlockDefaultLoginIgnoresNonLoginPaths(): void
    {
        $this->obfuscation->setLoginSlug('secret-login');
        $this->obfuscation->setRequestUri('/some-page');
        $this->obfuscation->setLoggedIn(false);

        $this->obfuscation->blockDefaultLogin();

        $this->assertFalse($this->obfuscation->sent404);
    }

    public function testFilterLoginUrl(): void
    {
        $this->obfuscation->setLoginSlug('secret-login');

        $result = $this->obfuscation->filterLoginUrl('https://example.com/wp-login.php', '');
        $this->assertSame('https://example.com/secret-login', $result);
    }

    public function testFilterLogoutUrl(): void
    {
        $this->obfuscation->setLoginSlug('secret-login');

        $result = $this->obfuscation->filterLogoutUrl('https://example.com/wp-login.php?action=logout', '');
        $this->assertSame('https://example.com/secret-login?action=logout', $result);
    }

    public function testFilterSiteUrlReplacesWpLogin(): void
    {
        $this->obfuscation->setLoginSlug('secret-login');

        $result = $this->obfuscation->filterSiteUrl('https://example.com/wp-login.php', 'wp-login.php', 'https');
        $this->assertSame('https://example.com/secret-login', $result);
    }

    public function testFilterSiteUrlIgnoresNonLoginPaths(): void
    {
        $this->obfuscation->setLoginSlug('secret-login');

        $result = $this->obfuscation->filterSiteUrl('https://example.com/wp-admin/', 'wp-admin/', 'https');
        $this->assertSame('https://example.com/wp-admin/', $result);
    }

    public function testUriMatchesSlugExactMatch(): void
    {
        $this->assertTrue($this->obfuscation->uriMatchesSlug('/secret-login', '/secret-login'));
    }

    public function testUriMatchesSlugWithTrailingSlash(): void
    {
        $this->assertTrue($this->obfuscation->uriMatchesSlug('/secret-login/', '/secret-login'));
    }

    public function testUriMatchesSlugWithQueryString(): void
    {
        $this->assertTrue($this->obfuscation->uriMatchesSlug('/secret-login?redirect_to=/wp-admin/', '/secret-login'));
    }

    public function testUriDoesNotMatchDifferentSlug(): void
    {
        $this->assertFalse($this->obfuscation->uriMatchesSlug('/other-page', '/secret-login'));
    }

    public function testIsDefaultLoginRequest(): void
    {
        $this->assertTrue($this->obfuscation->isDefaultLoginRequest('/wp-login.php'));
        $this->assertTrue($this->obfuscation->isDefaultLoginRequest('/wp-login.php?action=logout'));
        $this->assertFalse($this->obfuscation->isDefaultLoginRequest('/wp-admin/'));
        $this->assertFalse($this->obfuscation->isDefaultLoginRequest('/secret-login'));
    }
}
