<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\CapabilityHardening;
use BackTo\Framework\Security\Contracts\AuditLogRepositoryInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use PHPUnit\Framework\TestCase;

class TestableCapabilityHardening extends CapabilityHardening
{
    private int $currentUserId = 1;
    public bool $roleReverted = false;
    public string $revertedToRole = '';

    public function setCurrentUserId(int $id): void
    {
        $this->currentUserId = $id;
    }

    protected function getCurrentUserId(): int
    {
        return $this->currentUserId;
    }

    protected function getClientIp(): string
    {
        return '1.2.3.4';
    }

    protected function revertRole(int $userId, array $oldRoles): void
    {
        $this->roleReverted = true;
        $this->revertedToRole = $oldRoles[0] ?? 'subscriber';
    }
}

class CapabilityHardeningTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private LoggerInterface $logger;
    private AuditLogRepositoryInterface $auditLog;
    private RequestContextInterface $requestContext;
    private TestableCapabilityHardening $hardening;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->auditLog = $this->createMock(AuditLogRepositoryInterface::class);
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->requestContext->method('getRemoteAddr')->willReturn('127.0.0.1');
        $this->requestContext->method('server')->willReturn('');
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->hardening = new TestableCapabilityHardening(
            $this->dispatcher,
            $this->logger,
            $this->auditLog,
            $this->requestContext,
        );
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->hardening);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->hardening);
    }

    public function testGetName(): void
    {
        $this->assertSame('capability_hardening', $this->hardening->getName());
    }

    public function testHooksRegistersActionAndFilter(): void
    {
        $this->dispatcher->expects($this->once())->method('addAction')
            ->with('set_user_role', $this->anything(), 5, 3);
        $this->dispatcher->expects($this->once())->method('addFilter')
            ->with('user_has_cap', $this->anything(), 10, 4);

        $this->hardening->hooks();
    }

    public function testIsPrivilegedRole(): void
    {
        $this->assertTrue($this->hardening->isPrivilegedRole('administrator'));
        $this->assertFalse($this->hardening->isPrivilegedRole('editor'));
        $this->assertFalse($this->hardening->isPrivilegedRole('subscriber'));
    }

    public function testHasPrivilegedRole(): void
    {
        $this->assertTrue($this->hardening->hasPrivilegedRole(['administrator', 'editor']));
        $this->assertFalse($this->hardening->hasPrivilegedRole(['editor', 'author']));
    }

    public function testOnRoleChangeBlocksSelfPromotion(): void
    {
        $this->hardening->setCurrentUserId(42);

        $this->logger->expects($this->once())->method('warning');
        $this->auditLog->expects($this->once())
            ->method('store')
            ->with('self_promotion_blocked', 'critical', $this->anything());

        $this->hardening->onRoleChange(42, 'administrator', ['subscriber']);

        $this->assertTrue($this->hardening->roleReverted);
        $this->assertSame('subscriber', $this->hardening->revertedToRole);
    }

    public function testOnRoleChangeAllowsPromotionByOtherAdmin(): void
    {
        $this->hardening->setCurrentUserId(1); // Admin user
        // Promoting user 42 (not self)

        $this->auditLog->expects($this->once())
            ->method('store')
            ->with('privileged_role_granted', 'warning', $this->anything());

        $this->hardening->onRoleChange(42, 'administrator', ['editor']);

        $this->assertFalse($this->hardening->roleReverted);
    }

    public function testOnRoleChangeIgnoresNonPrivilegedRoleChange(): void
    {
        $this->auditLog->expects($this->never())->method('store');

        $this->hardening->onRoleChange(42, 'editor', ['subscriber']);

        $this->assertFalse($this->hardening->roleReverted);
    }

    public function testOnRoleChangeIgnoresAlreadyPrivilegedUser(): void
    {
        $this->auditLog->expects($this->never())->method('store');

        $this->hardening->onRoleChange(42, 'administrator', ['administrator']);

        $this->assertFalse($this->hardening->roleReverted);
    }

    public function testOnRoleChangeRespectsSelfPromotionToggle(): void
    {
        $this->hardening->setBlockSelfPromotion(false);
        $this->hardening->setCurrentUserId(42);

        // Should log promotion, not block
        $this->auditLog->expects($this->once())
            ->method('store')
            ->with('privileged_role_granted', 'warning', $this->anything());

        $this->hardening->onRoleChange(42, 'administrator', ['subscriber']);

        $this->assertFalse($this->hardening->roleReverted);
    }

    public function testFilterCapabilitiesBlocksPromoteForNonAdmin(): void
    {
        $allCaps = ['edit_posts' => true, 'promote_users' => true];
        $caps = ['promote_users'];
        $args = ['promote_users'];

        $result = $this->hardening->filterCapabilities($allCaps, $caps, $args, null);

        $this->assertFalse($result['promote_users']);
    }

    public function testFilterCapabilitiesAllowsPromoteForAdmin(): void
    {
        $allCaps = ['manage_options' => true, 'promote_users' => true];
        $caps = ['promote_users'];
        $args = ['promote_users'];

        $result = $this->hardening->filterCapabilities($allCaps, $caps, $args, null);

        $this->assertTrue($result['promote_users']);
    }

    public function testFilterCapabilitiesIgnoresNonPromoteCaps(): void
    {
        $allCaps = ['edit_posts' => true];
        $caps = ['edit_posts'];
        $args = ['edit_posts'];

        $result = $this->hardening->filterCapabilities($allCaps, $caps, $args, null);

        $this->assertSame($allCaps, $result);
    }

    public function testGetSensitiveCapabilities(): void
    {
        $caps = $this->hardening->getSensitiveCapabilities();

        $this->assertContains('manage_options', $caps);
        $this->assertContains('install_plugins', $caps);
        $this->assertContains('unfiltered_html', $caps);
    }
}
