<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Bundle\Security\Contracts\MailerInterface;
use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckStatus;
use BackTo\Framework\Observability\HealthCheck\SmtpHealthCheck;
use BackTo\Framework\Observability\HealthCheck\SmtpHealthCheckEnvironment;
use PHPUnit\Framework\TestCase;

class SmtpHealthCheckTest extends TestCase
{
    private MailerInterface $mailer;
    private SmtpHealthCheckEnvironment $environment;
    private SmtpHealthCheck $check;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->environment = $this->createMock(SmtpHealthCheckEnvironment::class);
        $this->check = new SmtpHealthCheck($this->mailer, $this->environment);
    }

    public function testImplementsHealthCheckInterface(): void
    {
        $this->assertInstanceOf(HealthCheckInterface::class, $this->check);
    }

    public function testGetName(): void
    {
        $this->assertSame('smtp', $this->check->getName());
    }

    public function testHealthyWhenMailSends(): void
    {
        $this->environment->method('getCachedResult')->willReturn(null);
        $this->environment->method('getAdminEmail')->willReturn('admin@example.com');
        $this->environment->method('getSiteName')->willReturn('Test Site');
        $this->environment->expects($this->once())->method('setCachedResult')
            ->with($this->anything(), 'healthy', $this->anything());

        $this->mailer->method('send')->willReturn(true);

        $result = $this->check->check();

        $this->assertTrue($result->isHealthy());
        $this->assertStringContainsString('SMTP operational', $result->getMessage());
        $this->assertArrayHasKey('response_time_ms', $result->getMetadata());
        $this->assertSame('admin@example.com', $result->getMetadata()['recipient']);
    }

    public function testUnhealthyWhenMailFails(): void
    {
        $this->environment->method('getCachedResult')->willReturn(null);
        $this->environment->method('getAdminEmail')->willReturn('admin@example.com');
        $this->environment->method('getSiteName')->willReturn('Test Site');
        $this->environment->expects($this->once())->method('setCachedResult')
            ->with($this->anything(), 'unhealthy', $this->anything());

        $this->mailer->method('send')->willReturn(false);

        $result = $this->check->check();

        $this->assertSame(HealthCheckStatus::Unhealthy, $result->getHealthCheckStatus());
        $this->assertStringContainsString('wp_mail()', $result->getMessage());
    }

    public function testUnhealthyOnException(): void
    {
        $this->environment->method('getCachedResult')->willReturn(null);
        $this->environment->method('getAdminEmail')->willReturn('admin@example.com');
        $this->environment->method('getSiteName')->willReturn('Test Site');

        $this->mailer->method('send')->willThrowException(
            new \RuntimeException('SMTP timeout'),
        );

        $result = $this->check->check();

        $this->assertSame(HealthCheckStatus::Unhealthy, $result->getHealthCheckStatus());
        $this->assertStringContainsString('SMTP timeout', $result->getMessage());
    }

    public function testDegradedWhenNoAdminEmail(): void
    {
        $this->environment->method('getCachedResult')->willReturn(null);
        $this->environment->method('getAdminEmail')->willReturn('');

        $result = $this->check->check();

        $this->assertSame(HealthCheckStatus::Degraded, $result->getHealthCheckStatus());
        $this->assertStringContainsString('No admin email', $result->getMessage());
    }

    public function testReturnsCachedHealthyResult(): void
    {
        $this->environment->method('getCachedResult')->willReturn('healthy');

        $this->mailer->expects($this->never())->method('send');

        $result = $this->check->check();

        $this->assertTrue($result->isHealthy());
        $this->assertStringContainsString('cached', $result->getMessage());
    }

    public function testReturnsCachedUnhealthyResult(): void
    {
        $this->environment->method('getCachedResult')->willReturn('unhealthy');

        $this->mailer->expects($this->never())->method('send');

        $result = $this->check->check();

        $this->assertSame(HealthCheckStatus::Unhealthy, $result->getHealthCheckStatus());
        $this->assertStringContainsString('cached', $result->getMessage());
    }
}
