<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Exception\FrameworkException;
use BackTo\Framework\Queue\Entity\Job;
use BackTo\Framework\Queue\Entity\JobStatus;
use BackTo\Framework\Queue\Factory\JobFactory;
use PHPUnit\Framework\TestCase;

class JobFactoryTest extends TestCase
{
    private JobFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new JobFactory();
    }

    public function testCreateJobWithDefaults(): void
    {
        $job = $this->factory->create('send_email');

        $this->assertSame('send_email', $job->getKey());
        $this->assertSame('default', $job->getGroup());
        $this->assertSame([], $job->getPayload());
        $this->assertSame(JobStatus::Pending, $job->getStatus());
        $this->assertSame(0, $job->getAttempts());
        $this->assertSame(3, $job->getMaxRetries());
        $this->assertSame(0, $job->getIntervalSeconds());
        $this->assertNotNull($job->getScheduledAt());
        $this->assertNotNull($job->getCreatedAt());
    }

    public function testCreateJobWithCustomParams(): void
    {
        $payload = ['to' => 'test@example.com', 'subject' => 'Hello'];

        $job = $this->factory->create(
            'send_email',
            $payload,
            'emails',
            5,
            60,
            3600
        );

        $this->assertSame('send_email', $job->getKey());
        $this->assertSame('emails', $job->getGroup());
        $this->assertSame($payload, $job->getPayload());
        $this->assertSame(5, $job->getMaxRetries());
        $this->assertSame(3600, $job->getIntervalSeconds());
        $this->assertTrue($job->isRecurring());
    }

    public function testCreateJobWithDelay(): void
    {
        $before = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $job = $this->factory->create('send_email', [], 'default', 3, 120);
        $expectedMin = $before->modify('+120 seconds');

        $this->assertNotNull($job->getScheduledAt());
        $this->assertGreaterThanOrEqual($expectedMin, $job->getScheduledAt());
    }

    public function testCreateJobWithEmptyKeyThrows(): void
    {
        $this->expectException(FrameworkException::class);
        $this->expectExceptionMessage('Job key cannot be empty.');

        $this->factory->create('');
    }

    public function testFromRowHydration(): void
    {
        $row = [
            'id' => '42',
            'job_key' => 'process_image',
            'job_group' => 'media',
            'payload' => '{"url":"https://example.com/image.jpg"}',
            'status' => 'running',
            'attempts' => '1',
            'max_retries' => '5',
            'last_error' => 'Timeout',
            'interval_seconds' => '0',
            'scheduled_at' => '2025-01-15 10:30:00',
            'claimed_at' => '2025-01-15 10:31:00',
            'completed_at' => '0000-00-00 00:00:00',
            'created_at' => '2025-01-15 10:00:00',
        ];

        $job = $this->factory->fromRow($row);

        $this->assertSame(42, $job->getId());
        $this->assertSame('process_image', $job->getKey());
        $this->assertSame('media', $job->getGroup());
        $this->assertSame(['url' => 'https://example.com/image.jpg'], $job->getPayload());
        $this->assertSame(JobStatus::Running, $job->getStatus());
        $this->assertSame(1, $job->getAttempts());
        $this->assertSame(5, $job->getMaxRetries());
        $this->assertSame('Timeout', $job->getLastError());
        $this->assertNotNull($job->getScheduledAt());
        $this->assertNotNull($job->getClaimedAt());
        $this->assertNull($job->getCompletedAt());
        $this->assertNotNull($job->getCreatedAt());
        $this->assertFalse($job->isRecurring());
    }

    public function testFromRowWithEmptyPayload(): void
    {
        $row = [
            'id' => '1',
            'job_key' => 'cleanup',
            'payload' => '',
        ];

        $job = $this->factory->fromRow($row);

        $this->assertSame([], $job->getPayload());
    }

    public function testFromRowWithInvalidStatus(): void
    {
        $row = [
            'id' => '1',
            'job_key' => 'test',
            'status' => 'invalid_status',
        ];

        $job = $this->factory->fromRow($row);

        $this->assertSame(JobStatus::Pending, $job->getStatus());
    }
}
