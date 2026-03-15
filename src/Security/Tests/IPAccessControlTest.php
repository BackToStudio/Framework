<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\IPAccessControlInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\IPAccessControl;
use PHPUnit\Framework\TestCase;

class TestableIPAccessControl extends IPAccessControl
{
    private string $clientIp = '1.2.3.4';
    public bool $accessDenied = false;

    public function setClientIp(string $ip): void
    {
        $this->clientIp = $ip;
    }

    protected function getClientIp(): string
    {
        return $this->clientIp;
    }

    protected function denyAccess(): void
    {
        $this->accessDenied = true;
    }
}

class IPAccessControlTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private LoggerInterface $logger;
    private TestableIPAccessControl $acl;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->acl = new TestableIPAccessControl($this->dispatcher, $this->logger);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->acl);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->acl);
        $this->assertInstanceOf(IPAccessControlInterface::class, $this->acl);
    }

    public function testGetName(): void
    {
        $this->assertSame('ip_access_control', $this->acl->getName());
    }

    public function testHooksRegistersActions(): void
    {
        $hooks = [];
        $this->dispatcher->expects($this->exactly(2))
            ->method('addAction')
            ->willReturnCallback(function (string $hook) use (&$hooks) {
                $hooks[] = $hook;
            });

        $this->acl->hooks();

        $this->assertSame(['admin_init', 'login_init'], $hooks);
    }

    public function testAddToWhitelist(): void
    {
        $this->acl->addToWhitelist('10.0.0.1');

        $this->assertSame(['10.0.0.1'], $this->acl->getWhitelist());
    }

    public function testAddToWhitelistDeduplicates(): void
    {
        $this->acl->addToWhitelist('10.0.0.1');
        $this->acl->addToWhitelist('10.0.0.1');

        $this->assertSame(['10.0.0.1'], $this->acl->getWhitelist());
    }

    public function testAddToBlacklist(): void
    {
        $this->acl->addToBlacklist('192.168.1.100');

        $this->assertSame(['192.168.1.100'], $this->acl->getBlacklist());
    }

    public function testIsAllowedWithWhitelist(): void
    {
        $this->acl->addToWhitelist('10.0.0.1');

        $this->assertTrue($this->acl->isAllowed('10.0.0.1'));
        $this->assertFalse($this->acl->isAllowed('10.0.0.2'));
    }

    public function testIsAllowedWithBlacklist(): void
    {
        $this->acl->addToBlacklist('192.168.1.100');

        $this->assertFalse($this->acl->isAllowed('192.168.1.100'));
        $this->assertTrue($this->acl->isAllowed('192.168.1.101'));
    }

    public function testIsAllowedWithNoListsAllows(): void
    {
        $this->assertTrue($this->acl->isAllowed('1.2.3.4'));
    }

    public function testIsBlocked(): void
    {
        $this->acl->addToBlacklist('192.168.1.100');

        $this->assertTrue($this->acl->isBlocked('192.168.1.100'));
        $this->assertFalse($this->acl->isBlocked('192.168.1.101'));
    }

    public function testMatchesCidrExactIp(): void
    {
        $this->assertTrue($this->acl->matchesCidr('10.0.0.1', '10.0.0.1'));
        $this->assertFalse($this->acl->matchesCidr('10.0.0.2', '10.0.0.1'));
    }

    public function testMatchesCidrSubnet24(): void
    {
        $this->assertTrue($this->acl->matchesCidr('192.168.1.50', '192.168.1.0/24'));
        $this->assertTrue($this->acl->matchesCidr('192.168.1.255', '192.168.1.0/24'));
        $this->assertFalse($this->acl->matchesCidr('192.168.2.1', '192.168.1.0/24'));
    }

    public function testMatchesCidrSubnet16(): void
    {
        $this->assertTrue($this->acl->matchesCidr('10.0.50.1', '10.0.0.0/16'));
        $this->assertFalse($this->acl->matchesCidr('10.1.0.1', '10.0.0.0/16'));
    }

    public function testWhitelistWithCidr(): void
    {
        $this->acl->addToWhitelist('10.0.0.0/24');

        $this->assertTrue($this->acl->isAllowed('10.0.0.50'));
        $this->assertFalse($this->acl->isAllowed('10.0.1.1'));
    }

    public function testBlacklistWithCidr(): void
    {
        $this->acl->addToBlacklist('192.168.1.0/24');

        $this->assertTrue($this->acl->isBlocked('192.168.1.50'));
        $this->assertFalse($this->acl->isBlocked('192.168.2.1'));
    }

    public function testCheckAdminAccessDeniesBlacklistedIp(): void
    {
        $this->acl->addToBlacklist('1.2.3.4');
        $this->acl->setClientIp('1.2.3.4');

        $this->logger->expects($this->once())->method('warning');

        $this->acl->checkAdminAccess();

        $this->assertTrue($this->acl->accessDenied);
    }

    public function testCheckAdminAccessAllowsWhitelistedIp(): void
    {
        $this->acl->addToWhitelist('1.2.3.4');
        $this->acl->setClientIp('1.2.3.4');

        $this->acl->checkAdminAccess();

        $this->assertFalse($this->acl->accessDenied);
    }

    public function testCheckAdminAccessAllowsWhenNoLists(): void
    {
        $this->acl->setClientIp('1.2.3.4');

        $this->acl->checkAdminAccess();

        $this->assertFalse($this->acl->accessDenied);
    }

    public function testCheckLoginAccessDeniesBlacklistedIp(): void
    {
        $this->acl->addToBlacklist('1.2.3.4');
        $this->acl->setClientIp('1.2.3.4');

        $this->acl->checkLoginAccess();

        $this->assertTrue($this->acl->accessDenied);
    }

    public function testFluentInterface(): void
    {
        $result = $this->acl
            ->addToWhitelist('10.0.0.1')
            ->addToBlacklist('192.168.1.100');

        $this->assertSame($this->acl, $result);
    }
}
