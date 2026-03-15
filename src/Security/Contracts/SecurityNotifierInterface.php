<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Contracts;

/**
 * Port interface for sending security notifications.
 */
interface SecurityNotifierInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function notify(string $event, string $severity, array $context): void;

    /**
     * @param string[] $emails
     */
    public function setRecipients(array $emails): self;

    /**
     * @return string[]
     */
    public function getRecipients(): array;
}
