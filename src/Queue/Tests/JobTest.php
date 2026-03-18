<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Queue\Entity\Job;
use BackTo\Framework\Queue\Entity\JobStatus;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $job = new Job();

        $this->assertSame(0, $job->getId());
        $this->assertSame('', $job->getKey());
        $this->assertSame('default', $job->getGroup());
        $this->assertSame([], $job->getPayload());
        $this->assertSame('', $job->getPayloadHash());
        $this->assertSame(JobStatus::Pending, $job->getStatus());
        $this->assertSame(0, $job->getAttempts());
        $this->assertSame(3, $job->getMaxRetries());
        $this->assertSame('', $job->getLastError());
        $this->assertSame('', $job->getClaimToken());
        $this->assertNull($job->getScheduledAt());
        $this->assertNull($job->getClaimedAt());
        $this->assertNull($job->getCompletedAt());
        $this->assertNull($job->getCreatedAt());
        $this->assertNull($job->getUpdatedAt());
        $this->assertSame(0, $job->getIntervalSeconds());
        $this->assertFalse($job->isRecurring());
    }

    public function testFluentSetters(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $job = new Job();

        $result = $job->setId(42)
            ->setKey('send_email')
            ->setGroup('emails')
            ->setPayload(['to' => 'test@example.com'])
            ->setPayloadHash('abc123')
            ->setStatus(JobStatus::Running)
            ->setAttempts(2)
            ->setMaxRetries(5)
            ->setLastError('Timeout')
            ->setClaimToken('token-xyz')
            ->setScheduledAt($now)
            ->setClaimedAt($now)
            ->setCompletedAt($now)
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setIntervalSeconds(3600);

        $this->assertSame($job, $result);
        $this->assertSame(42, $job->getId());
        $this->assertSame('send_email', $job->getKey());
        $this->assertSame('emails', $job->getGroup());
        $this->assertSame(['to' => 'test@example.com'], $job->getPayload());
        $this->assertSame('abc123', $job->getPayloadHash());
        $this->assertSame(JobStatus::Running, $job->getStatus());
        $this->assertSame(2, $job->getAttempts());
        $this->assertSame(5, $job->getMaxRetries());
        $this->assertSame('Timeout', $job->getLastError());
        $this->assertSame('token-xyz', $job->getClaimToken());
        $this->assertSame($now, $job->getScheduledAt());
        $this->assertSame($now, $job->getClaimedAt());
        $this->assertSame($now, $job->getCompletedAt());
        $this->assertSame($now, $job->getCreatedAt());
        $this->assertSame($now, $job->getUpdatedAt());
        $this->assertSame(3600, $job->getIntervalSeconds());
    }

    public function testIsReadyWithPendingStatus(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Pending);

        $this->assertTrue($job->isReady());
    }

    public function testIsReadyWithRunningStatus(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Running);

        $this->assertFalse($job->isReady());
    }

    public function testIsReadyWithFutureScheduledAt(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Pending)
            ->setScheduledAt(new \DateTimeImmutable('+1 hour', new \DateTimeZone('UTC')));

        $this->assertFalse($job->isReady());
    }

    public function testIsReadyWithPastScheduledAt(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Pending)
            ->setScheduledAt(new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC')));

        $this->assertTrue($job->isReady());
    }

    public function testCanRetryWithAttemptsRemaining(): void
    {
        $job = new Job();
        $job->setMaxRetries(3)->setAttempts(1);

        $this->assertTrue($job->canRetry());
    }

    public function testCanRetryWithAttemptsExhausted(): void
    {
        $job = new Job();
        $job->setMaxRetries(3)->setAttempts(3);

        $this->assertFalse($job->canRetry());
    }

    public function testCanRetryWithZeroMaxRetries(): void
    {
        $job = new Job();
        $job->setMaxRetries(0)->setAttempts(0);

        $this->assertFalse($job->canRetry());
    }

    public function testIsRecurringWithInterval(): void
    {
        $job = new Job();
        $job->setIntervalSeconds(3600);

        $this->assertTrue($job->isRecurring());
    }

    public function testIsRecurringWithoutInterval(): void
    {
        $job = new Job();
        $job->setIntervalSeconds(0);

        $this->assertFalse($job->isRecurring());
    }

    public function testDateGettersReturnNullBeforeSetting(): void
    {
        $job = new Job();

        $this->assertNull($job->getScheduledAt());
        $this->assertNull($job->getClaimedAt());
        $this->assertNull($job->getCompletedAt());
        $this->assertNull($job->getCreatedAt());
        $this->assertNull($job->getUpdatedAt());
    }

    public function testCanRetryWhenAttemptsExceedMaxRetries(): void
    {
        $job = new Job();
        $job->setMaxRetries(3)->setAttempts(5);

        $this->assertFalse($job->canRetry());
    }

    public function testCanRetryWithOneRetryLeft(): void
    {
        $job = new Job();
        $job->setMaxRetries(3)->setAttempts(2);

        $this->assertTrue($job->canRetry());
    }

    public function testIsRecurringWithNegativeInterval(): void
    {
        $job = new Job();
        $job->setIntervalSeconds(-1);

        // Negative interval should not be considered recurring
        $this->assertFalse($job->isRecurring());
    }
}
