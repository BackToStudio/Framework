<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Queue\Entity\JobStatus;
use PHPUnit\Framework\TestCase;

class JobStatusTest extends TestCase
{
    public function testPendingValue(): void
    {
        $this->assertSame('pending', JobStatus::Pending->value);
    }

    public function testRunningValue(): void
    {
        $this->assertSame('running', JobStatus::Running->value);
    }

    public function testCompletedValue(): void
    {
        $this->assertSame('completed', JobStatus::Completed->value);
    }

    public function testFailedValue(): void
    {
        $this->assertSame('failed', JobStatus::Failed->value);
    }

    public function testCancelledValue(): void
    {
        $this->assertSame('cancelled', JobStatus::Cancelled->value);
    }

    public function testTryFromWithValidValue(): void
    {
        $this->assertSame(JobStatus::Pending, JobStatus::tryFrom('pending'));
        $this->assertSame(JobStatus::Running, JobStatus::tryFrom('running'));
        $this->assertSame(JobStatus::Completed, JobStatus::tryFrom('completed'));
        $this->assertSame(JobStatus::Failed, JobStatus::tryFrom('failed'));
        $this->assertSame(JobStatus::Cancelled, JobStatus::tryFrom('cancelled'));
    }

    public function testTryFromWithInvalidValueReturnsNull(): void
    {
        $this->assertNull(JobStatus::tryFrom('unknown'));
        $this->assertNull(JobStatus::tryFrom(''));
    }

    public function testAllCasesCount(): void
    {
        $this->assertCount(5, JobStatus::cases());
    }
}
