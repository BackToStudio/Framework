<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Queue\Contracts\JobInterface;
use BackTo\Framework\Queue\QueueRegistry;
use PHPUnit\Framework\TestCase;

class SendEmailJob implements JobInterface
{
    public function getKey(): string
    {
        return 'send_email';
    }

    public function getLabel(): string
    {
        return 'Send Email';
    }

    public function getGroup(): string
    {
        return 'emails';
    }

    public function getMaxRetries(): int
    {
        return 3;
    }

    public function handle(array $payload): void
    {
    }
}

class ProcessImageJob implements JobInterface
{
    public function getKey(): string
    {
        return 'process_image';
    }

    public function getLabel(): string
    {
        return 'Process Image';
    }

    public function getGroup(): string
    {
        return 'media';
    }

    public function getMaxRetries(): int
    {
        return 5;
    }

    public function handle(array $payload): void
    {
    }
}

class QueueRegistryTest extends TestCase
{
    public function testRegistryShouldImplementRegistryInterface(): void
    {
        $registry = new QueueRegistry();

        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyRegistry(): void
    {
        $registry = new QueueRegistry();

        $this->assertCount(0, $registry->getJobs());
    }

    public function testAddingJobs(): void
    {
        $registry = new QueueRegistry();
        $registry->add(new SendEmailJob());
        $registry->add(new ProcessImageJob());

        $this->assertCount(2, $registry->getJobs());
    }

    public function testGetJobByKey(): void
    {
        $registry = new QueueRegistry();
        $job = new SendEmailJob();
        $registry->add($job);

        $found = $registry->get('send_email');

        $this->assertNotNull($found);
        $this->assertSame('send_email', $found->getKey());
    }

    public function testGetNonExistentJobReturnsNull(): void
    {
        $registry = new QueueRegistry();

        $this->assertNull($registry->get('non_existent'));
    }

    public function testAddingDuplicateKeyOverwrites(): void
    {
        $registry = new QueueRegistry();
        $registry->add(new SendEmailJob());
        $registry->add(new SendEmailJob());

        $this->assertCount(1, $registry->getJobs());
    }
}
