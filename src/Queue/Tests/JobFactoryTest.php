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

    public function testCreateJobWithKeyTooLongThrows(): void
    {
        $this->expectException(FrameworkException::class);
        $this->expectExceptionMessage('Job key cannot exceed 255 characters.');

        $this->factory->create(\str_repeat('a', 256));
    }

    public function testCreateJobWithGroupTooLongThrows(): void
    {
        $this->expectException(FrameworkException::class);
        $this->expectExceptionMessage('Job group cannot exceed 255 characters.');

        $this->factory->create('valid_key', [], \str_repeat('g', 256));
    }

    public function testCreateJobWithNegativeDelayClampsToZero(): void
    {
        $before = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $job = $this->factory->create('send_email', [], 'default', 3, -10);

        $this->assertNotNull($job->getScheduledAt());
        // Should be scheduled at now, not in the past.
        $this->assertGreaterThanOrEqual($before, $job->getScheduledAt());
        $this->assertLessThanOrEqual($before->modify('+2 seconds'), $job->getScheduledAt());
    }

    public function testCreateJobWithNegativeIntervalClampsToZero(): void
    {
        $job = $this->factory->create('send_email', [], 'default', 3, 0, -600);

        $this->assertSame(0, $job->getIntervalSeconds());
        $this->assertFalse($job->isRecurring());
    }

    public function testCreateJobComputesPayloadHash(): void
    {
        $payload = ['to' => 'user@example.com'];
        $job = $this->factory->create('send_email', $payload);

        $expectedHash = \md5(\json_encode($payload, \JSON_THROW_ON_ERROR));
        $this->assertSame($expectedHash, $job->getPayloadHash());
    }

    public function testCreateJobEmptyPayloadHasConsistentHash(): void
    {
        $job1 = $this->factory->create('job_a');
        $job2 = $this->factory->create('job_b');

        $this->assertSame($job1->getPayloadHash(), $job2->getPayloadHash());
        $this->assertSame(\md5('[]'), $job1->getPayloadHash());
    }

    public function testFromRowHydration(): void
    {
        $row = [
            'id' => '42',
            'job_key' => 'process_image',
            'job_group' => 'media',
            'payload' => '{"url":"https://example.com/image.jpg"}',
            'payload_hash' => 'abc123',
            'status' => 'running',
            'attempts' => '1',
            'max_retries' => '5',
            'last_error' => 'Timeout',
            'claim_token' => 'token123',
            'interval_seconds' => '0',
            'scheduled_at' => '2025-01-15 10:30:00',
            'claimed_at' => '2025-01-15 10:31:00',
            'completed_at' => '0000-00-00 00:00:00',
            'created_at' => '2025-01-15 10:00:00',
            'updated_at' => '2025-01-15 10:31:00',
        ];

        $job = $this->factory->fromRow($row);

        $this->assertSame(42, $job->getId());
        $this->assertSame('process_image', $job->getKey());
        $this->assertSame('media', $job->getGroup());
        $this->assertSame(['url' => 'https://example.com/image.jpg'], $job->getPayload());
        $this->assertSame('abc123', $job->getPayloadHash());
        $this->assertSame(JobStatus::Running, $job->getStatus());
        $this->assertSame(1, $job->getAttempts());
        $this->assertSame(5, $job->getMaxRetries());
        $this->assertSame('Timeout', $job->getLastError());
        $this->assertSame('token123', $job->getClaimToken());
        $this->assertNotNull($job->getScheduledAt());
        $this->assertNotNull($job->getClaimedAt());
        $this->assertNull($job->getCompletedAt());
        $this->assertNotNull($job->getCreatedAt());
        $this->assertNotNull($job->getUpdatedAt());
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

    public function testCreateJobWithPayloadTooLargeThrows(): void
    {
        $this->expectException(FrameworkException::class);
        $this->expectExceptionMessage('Job payload exceeds maximum size');

        // 1 MB + 1 byte payload.
        $largePayload = ['data' => \str_repeat('x', 1048577)];
        $this->factory->create('send_email', $largePayload);
    }

    public function testCreateJobWithPayloadAtLimitSucceeds(): void
    {
        // Just under 1 MB — should not throw.
        $payload = ['data' => \str_repeat('x', 1000000)];
        $job = $this->factory->create('send_email', $payload);

        $this->assertSame('send_email', $job->getKey());
    }

    public function testSanitizeErrorStripsFilePaths(): void
    {
        $message = 'Error in /home/user/app/src/Service/PaymentGateway.php on line 42';
        $sanitized = JobFactory::sanitizeError($message);

        $this->assertStringNotContainsString('/home/user/app', $sanitized);
        $this->assertStringContainsString('[path]', $sanitized);
        $this->assertStringContainsString('on line 42', $sanitized);
    }

    public function testSanitizeErrorStripsConnectionStrings(): void
    {
        $message = 'Connection failed: mysql://admin:s3cret@db.example.com:3306/mydb';
        $sanitized = JobFactory::sanitizeError($message);

        $this->assertStringNotContainsString('s3cret', $sanitized);
        $this->assertStringNotContainsString('db.example.com', $sanitized);
        $this->assertStringContainsString('[redacted-dsn]', $sanitized);
    }

    public function testSanitizeErrorStripsLongTokens(): void
    {
        $token = \str_repeat('a', 40);
        $message = "Authorization failed with token {$token} for user";
        $sanitized = JobFactory::sanitizeError($message);

        $this->assertStringNotContainsString($token, $sanitized);
        $this->assertStringContainsString('[redacted]', $sanitized);
    }

    public function testSanitizeErrorPreservesShortMessages(): void
    {
        $message = 'Connection timeout after 30s';
        $sanitized = JobFactory::sanitizeError($message);

        $this->assertSame($message, $sanitized);
    }

    public function testSanitizeErrorTruncatesLongMessages(): void
    {
        // Use a message with spaces to avoid token-redaction patterns.
        $message = \implode(' ', \array_fill(0, 1500, 'error occurred'));
        $sanitized = JobFactory::sanitizeError($message);

        $this->assertLessThanOrEqual(5000, \strlen($sanitized));
        $this->assertStringEndsWith(' [truncated]', $sanitized);
    }

    public function testTruncateErrorShortMessage(): void
    {
        $message = 'Short error';

        $this->assertSame($message, JobFactory::truncateError($message));
    }

    public function testTruncateErrorLongMessage(): void
    {
        $message = \str_repeat('x', 6000);

        $truncated = JobFactory::truncateError($message);

        $this->assertSame(5000, \strlen($truncated));
        $this->assertStringEndsWith(' [truncated]', $truncated);
    }

    public function testTruncateErrorExactLimit(): void
    {
        $message = \str_repeat('x', 5000);

        $this->assertSame($message, JobFactory::truncateError($message));
    }
}
