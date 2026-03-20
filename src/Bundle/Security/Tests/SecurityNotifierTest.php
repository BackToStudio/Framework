<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Cache\Contracts\TransientStoreInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;
use BackTo\Framework\Bundle\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Bundle\Security\Contracts\MailerInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityNotifierInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\SecurityNotifier;
use PHPUnit\Framework\TestCase;

class SecurityNotifierTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private MailerInterface $mailer;
    private OptionsRepositoryInterface $options;
    private ClientIpResolverInterface $ipResolver;
    private TransientStoreInterface $transientStore;
    private SecurityNotifier $notifier;

    /** @var array<int, array{to: string, subject: string, body: string}> */
    private array $sentEmails = [];

    /** @var array<string, mixed> In-memory store for transient mock */
    private array $transientData = [];

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);

        $this->sentEmails = [];
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->mailer->method('send')
            ->willReturnCallback(function (string $to, string $subject, string $body): bool {
                $this->sentEmails[] = ['to' => $to, 'subject' => $subject, 'body' => $body];
                return true;
            });

        $this->options = $this->createMock(OptionsRepositoryInterface::class);
        $this->options->method('get')
            ->willReturnMap([
                ['admin_email', '', 'admin@example.com'],
                ['blogname', 'WordPress', 'Test Site'],
            ]);

        $this->ipResolver = $this->createMock(ClientIpResolverInterface::class);
        $this->ipResolver->method('getClientIp')->willReturn('127.0.0.1');

        $this->transientData = [];
        $this->transientStore = $this->createMock(TransientStoreInterface::class);
        $this->transientStore->method('get')
            ->willReturnCallback(fn (string $key) => $this->transientData[$key] ?? false);
        $this->transientStore->method('set')
            ->willReturnCallback(function (string $key, mixed $value): bool {
                $this->transientData[$key] = $value;
                return true;
            });

        $this->notifier = new SecurityNotifier($this->dispatcher, $this->mailer, $this->options, $this->ipResolver, $this->transientStore);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->notifier);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->notifier);
        $this->assertInstanceOf(SecurityNotifierInterface::class, $this->notifier);
    }

    public function testGetName(): void
    {
        $this->assertSame('security_notifier', $this->notifier->getName());
    }

    public function testHooksRegistersActions(): void
    {
        $hooks = [];
        $this->dispatcher->expects($this->exactly(3))
            ->method('addAction')
            ->willReturnCallback(function (string $hook) use (&$hooks) {
                $hooks[] = $hook;
            });

        $this->notifier->hooks();

        $this->assertContains('backto_security_event', $hooks);
        $this->assertContains('set_user_role', $hooks);
        $this->assertContains('wp_login_failed', $hooks);
    }

    public function testNotifySendsEmailToAdminByDefault(): void
    {
        $this->notifier->notify('test_event', 'critical', ['key' => 'value']);

        $this->assertCount(1, $this->sentEmails);
        $this->assertSame('admin@example.com', $this->sentEmails[0]['to']);
    }

    public function testNotifySendsToCustomRecipients(): void
    {
        $this->notifier->setRecipients(['a@test.com', 'b@test.com']);
        $this->notifier->notify('test_event', 'warning', []);

        $this->assertCount(2, $this->sentEmails);
        $this->assertSame('a@test.com', $this->sentEmails[0]['to']);
        $this->assertSame('b@test.com', $this->sentEmails[1]['to']);
    }

    public function testNotifyDoesNothingWithNoRecipients(): void
    {
        $options = $this->createMock(OptionsRepositoryInterface::class);
        $options->method('get')
            ->willReturnMap([
                ['admin_email', '', ''],
                ['blogname', 'WordPress', 'Test Site'],
            ]);

        $notifier = new SecurityNotifier($this->dispatcher, $this->mailer, $options, $this->ipResolver, $this->transientStore);
        $notifier->notify('test_event', 'critical', []);

        $this->assertCount(0, $this->sentEmails);
    }

    public function testBuildSubject(): void
    {
        $subject = $this->notifier->buildSubject('login_failed', 'warning');

        $this->assertStringContainsString('[WARNING]', $subject);
        $this->assertStringContainsString('Test Site', $subject);
        $this->assertStringContainsString('Login failed', $subject);
    }

    public function testBuildBody(): void
    {
        $body = $this->notifier->buildBody('login_failed', 'warning', [
            'username' => 'admin',
            'ip' => '1.2.3.4',
        ]);

        $this->assertStringContainsString('login failed', $body);
        $this->assertStringContainsString('WARNING', $body);
        $this->assertStringContainsString('Username: admin', $body);
        $this->assertStringContainsString('Ip: 1.2.3.4', $body);
        $this->assertStringContainsString('BackTo Framework', $body);
    }

    public function testBuildBodyHandlesArrayContext(): void
    {
        $body = $this->notifier->buildBody('test', 'info', [
            'roles' => ['editor', 'author'],
        ]);

        $this->assertStringContainsString('editor, author', $body);
    }

    public function testOnSecurityEventNotifiesForCriticalEvent(): void
    {
        $this->notifier->onSecurityEvent('self_promotion_blocked', 'critical', ['user_id' => 42]);

        $this->assertCount(1, $this->sentEmails);
    }

    public function testOnSecurityEventIgnoresNonCriticalEvent(): void
    {
        $this->notifier->onSecurityEvent('some_unknown_event', 'info', []);

        $this->assertCount(0, $this->sentEmails);
    }

    public function testOnRoleChangeNotifiesForNewAdmin(): void
    {
        $this->notifier->onRoleChange(42, 'administrator', ['subscriber']);

        $this->assertCount(1, $this->sentEmails);
        $this->assertStringContainsString('Privileged role granted', $this->sentEmails[0]['subject']);
    }

    public function testOnRoleChangeIgnoresNonAdminRole(): void
    {
        $this->notifier->onRoleChange(42, 'editor', ['subscriber']);

        $this->assertCount(0, $this->sentEmails);
    }

    public function testOnRoleChangeIgnoresAlreadyAdmin(): void
    {
        $this->notifier->onRoleChange(42, 'administrator', ['administrator']);

        $this->assertCount(0, $this->sentEmails);
    }

    public function testOnLoginFailedNotifiesAtThreshold(): void
    {
        $this->notifier->setFailedLoginThreshold(3);

        $this->notifier->onLoginFailed('admin');
        $this->notifier->onLoginFailed('admin');
        $this->assertCount(0, $this->sentEmails);

        $this->notifier->onLoginFailed('admin');
        $this->assertCount(1, $this->sentEmails);
    }

    public function testOnLoginFailedDoesNotNotifyBeforeThreshold(): void
    {
        $this->notifier->setFailedLoginThreshold(5);

        for ($i = 0; $i < 4; $i++) {
            $this->notifier->onLoginFailed('admin');
        }

        $this->assertCount(0, $this->sentEmails);
    }

    public function testGetCriticalEvents(): void
    {
        $events = $this->notifier->getCriticalEvents();

        $this->assertContains('login_anomaly', $events);
        $this->assertContains('self_promotion_blocked', $events);
        $this->assertContains('file_integrity_failure', $events);
        $this->assertContains('malware_detected', $events);
    }

    public function testGetRecipients(): void
    {
        $this->assertSame([], $this->notifier->getRecipients());

        $this->notifier->setRecipients(['a@test.com']);
        $this->assertSame(['a@test.com'], $this->notifier->getRecipients());
    }
}
