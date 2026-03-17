<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Infrastructure;

use BackTo\Framework\Security\Contracts\MailerInterface;

/**
 * WordPress adapter for sending emails via wp_mail().
 */
class WordPressMailer implements MailerInterface
{
    public function send(string $to, string $subject, string $body): bool
    {
        return \wp_mail($to, $subject, $body);
    }
}
