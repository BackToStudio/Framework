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
        $this->assertSame(JobStatus::Pending, $job->getStatus());
        $this->assertSame(0, $job->getAttempts());
        $this->assertSame(3, $job->getMaxRetries());
        $this->assertSame('', $job->getLastError());
        $this->assertSame(0, $job->getIntervalSeconds());
        $this->assertFalse($job->isRecurring());
    }

    public function testFluentSetters(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $job = new Job();
        $result = $job->setId(1)
            ->setKey('send_email')
            ->setGroup('emails')
            ->setPayload(['to' => 'test@example.com'])
            ->setStatus(JobStatus::Running)
            ->setAttempts(2)
            ->setMaxRetries(5)
            ->setLastError('Timeout')
            ->setScheduledAt($now)
            ->setClaimedAt($now)
            ->setCompletedAt($now)
            ->setCreatedAt($now)
            ->setIntervalSeconds(3600);

        $this->assertSame($job, $result);
        $this->assertSame(1, $job->getId());
        $this->assertSame('send_email', $job->getKey());
        $this->assertSame('emails', $job->getGroup());
        $this->assertSame(['to' => 'test@example.com'], $job->getPayload());
        $this->assertSame(JobStatus::Running, $job->getStatus());
        $this->assertSame(2, $job->getAttempts());
        $this->assertSame(5, $job->getMaxRetries());
        $this->assertSame('Timeout', $job->getLastError());
        $this->assertSame($now, $job->getScheduledAt());
        $this->assertSame($now, $job->getClaimedAt());
        $this->assertSame($now, $job->getCompletedAt());
        $this->assertSame($now, $job->getCreatedAt());
        $this->assertSame(3600, $job->getIntervalSeconds());
        $this->assertTrue($job->isRecurring());
    }

    public function testIsReadyWhenPendingAndNoSchedule(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Pending);

        $this->assertTrue($job->isReady());
    }

    public function testIsReadyWhenScheduledInPast(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Pending)
            ->setScheduledAt(new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC')));

        $this->assertTrue($job->isReady());
    }

    public function testIsNotReadyWhenScheduledInFuture(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Pending)
            ->setScheduledAt(new \DateTimeImmutable('+1 hour', new \DateTimeZone('UTC')));

        $this->assertFalse($job->isReady());
    }

    public function testIsNotReadyWhenNotPending(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Running);

        $this->assertFalse($job->isReady());
    }

    public function testCanRetry(): void
    {
        $job = new Job();
        $job->setMaxRetries(3)->setAttempts(2);

        $this->assertTrue($job->canRetry());
    }

    public function testCannotRetryWhenMaxReached(): void
    {
        $job = new Job();
        $job->setMaxRetries(3)->setAttempts(3);

        $this->assertFalse($job->canRetry());
    }

    public function testCannotRetryWhenExceeded(): void
    {
        $job = new Job();
        $job->setMaxRetries(2)->setAttempts(5);

        $this->assertFalse($job->canRetry());
    }

    public function testCannotRetryWhenZeroRetries(): void
    {
        $job = new Job();
        $job->setMaxRetries(0)->setAttempts(0);

        $this->assertFalse($job->canRetry());
    }

    public function testIsNotRecurringByDefault(): void
    {
        $job = new Job();

        $this->assertFalse($job->isRecurring());
        $this->assertSame(0, $job->getIntervalSeconds());
    }

    public function testNullDatesBeforeSet(): void
    {
        $job = new Job();

        $this->assertNull($job->getScheduledAt());
        $this->assertNull($job->getClaimedAt());
        $this->assertNull($job->getCompletedAt());
        $this->assertNull($job->getCreatedAt());
    }

    public function testIsNotReadyForCompletedStatus(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Completed);

        $this->assertFalse($job->isReady());
    }

    public function testIsNotReadyForFailedStatus(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Failed);

        $this->assertFalse($job->isReady());
    }

    public function testIsNotReadyForCancelledStatus(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Cancelled);

        $this->assertFalse($job->isReady());
    }

    public function testIsReadyWhenScheduledAtExactlyNow(): void
    {
        $job = new Job();
        $job->setStatus(JobStatus::Pending)
            ->setScheduledAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));

        $this->assertTrue($job->isReady());
    }
}
