<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Contracts;

/**
 * Port interface for sending emails.
 */
interface MailerInterface
{
    public function send(string $to, string $subject, string $body): bool;
}
