<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Infrastructure;

use BackTo\Framework\Bundle\Security\Contracts\MailerInterface;

/**
 * WordPress adapter for sending emails via wp_mail().
 */
final class WordPressMailer implements MailerInterface
{
    public function send(string $to, string $subject, string $body): bool
    {
        return \wp_mail($to, $subject, $body);
    }
}
