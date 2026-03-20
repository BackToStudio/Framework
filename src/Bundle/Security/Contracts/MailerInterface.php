<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Port interface for sending emails.
 */
interface MailerInterface
{
    public function send(string $to, string $subject, string $body): bool;
}
