<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\AuditLogRepositoryInterface;
use BackTo\Framework\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\SecurityAuditLogger;
use PHPUnit\Framework\TestCase;

class SecurityAuditLoggerTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private AuditLogRepositoryInterface $repository;
    private ClientIpResolverInterface $ipResolver;
    private SecurityAuditLogger $logger;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->repository = $this->createMock(AuditLogRepositoryInterface::class);
        $this->ipResolver = $this->createMock(ClientIpResolverInterface::class);
        $this->ipResolver->method('getClientIp')->willReturn('127.0.0.1');
        $this->logger = new SecurityAuditLogger($this->dispatcher, $this->repository, $this->ipResolver);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->logger);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->logger);
    }

    public function testGetName(): void
    {
        $this->assertSame('security_audit_logger', $this->logger->getName());
    }

    public function testHooksRegistersAllActions(): void
    {
        $registeredHooks = [];

        $this->dispatcher->expects($this->exactly(9))
            ->method('addAction')
            ->willReturnCallback(function (string $hook) use (&$registeredHooks) {
                $registeredHooks[] = $hook;
            });

        $this->logger->hooks();

        $expectedHooks = [
            'wp_login',
            'wp_login_failed',
            'set_user_role',
            'updated_option',
            'activated_plugin',
            'deactivated_plugin',
            'switch_theme',
            'user_register',
            'delete_user',
        ];

        $this->assertSame($expectedHooks, $registeredHooks);
    }

    public function testOnLoginSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('store')
            ->with('login_success', 'info', $this->callback(function (array $ctx) {
                return $ctx['username'] === 'admin';
            }));

        $this->logger->onLoginSuccess('admin');
    }

    public function testOnLoginFailed(): void
    {
        $this->repository->expects($this->once())
            ->method('store')
            ->with('login_failed', 'warning', $this->callback(function (array $ctx) {
                return $ctx['username'] === 'hacker';
            }));

        $this->logger->onLoginFailed('hacker');
    }

    public function testOnUserRoleChanged(): void
    {
        $this->repository->expects($this->once())
            ->method('store')
            ->with('user_role_changed', 'warning', $this->callback(function (array $ctx) {
                return $ctx['user_id'] === 42
                    && $ctx['new_role'] === 'administrator'
                    && $ctx['old_roles'] === ['subscriber'];
            }));

        $this->logger->onUserRoleChanged(42, 'administrator', ['subscriber']);
    }

    public function testOnOptionUpdatedLogsCriticalOption(): void
    {
        $this->repository->expects($this->once())
            ->method('store')
            ->with('critical_option_changed', 'warning', $this->callback(function (array $ctx) {
                return $ctx['option'] === 'admin_email'
                    && $ctx['old_value'] === 'old@test.com'
                    && $ctx['new_value'] === 'new@test.com';
            }));

        $this->logger->onOptionUpdated('admin_email', 'old@test.com', 'new@test.com');
    }

    public function testOnOptionUpdatedIgnoresNonCriticalOption(): void
    {
        $this->repository->expects($this->never())->method('store');

        $this->logger->onOptionUpdated('blogname', 'old', 'new');
    }

    public function testOnPluginActivated(): void
    {
        $this->repository->expects($this->once())
            ->method('store')
            ->with('plugin_activated', 'info', $this->callback(function (array $ctx) {
                return $ctx['plugin'] === 'my-plugin/my-plugin.php';
            }));

        $this->logger->onPluginActivated('my-plugin/my-plugin.php');
    }

    public function testOnPluginDeactivated(): void
    {
        $this->repository->expects($this->once())
            ->method('store')
            ->with('plugin_deactivated', 'info', $this->anything());

        $this->logger->onPluginDeactivated('my-plugin/my-plugin.php');
    }

    public function testOnThemeSwitched(): void
    {
        $this->repository->expects($this->once())
            ->method('store')
            ->with('theme_switched', 'info', $this->callback(function (array $ctx) {
                return $ctx['new_theme'] === 'twentytwentyfive';
            }));

        $this->logger->onThemeSwitched('twentytwentyfive');
    }

    public function testOnUserCreated(): void
    {
        $this->repository->expects($this->once())
            ->method('store')
            ->with('user_created', 'info', $this->callback(function (array $ctx) {
                return $ctx['user_id'] === 99;
            }));

        $this->logger->onUserCreated(99);
    }

    public function testOnUserDeleted(): void
    {
        $this->repository->expects($this->once())
            ->method('store')
            ->with('user_deleted', 'warning', $this->callback(function (array $ctx) {
                return $ctx['user_id'] === 99;
            }));

        $this->logger->onUserDeleted(99);
    }

    public function testGetAuditLog(): void
    {
        $events = [['event' => 'login_success']];
        $this->repository->method('getEvents')->with([], 50, 0)->willReturn($events);

        $this->assertSame($events, $this->logger->getAuditLog(50, 0));
    }

    public function testPurgeOldEvents(): void
    {
        $this->repository->method('purge')->with(30)->willReturn(5);

        $this->assertSame(5, $this->logger->purgeOldEvents(30));
    }
}
