<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Alert;

use BackTo\Framework\Bundle\Security\Contracts\MailerInterface;
use BackTo\Framework\Observability\Contracts\AlertChannelInterface;
use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;

/**
 * Alert channel that sends alerts via email to the site administrator.
 *
 * Only sends emails for 'critical' and 'warning' levels to prevent
 * alert fatigue. Informational alerts are silently skipped.
 */
final class EmailAlertChannel implements AlertChannelInterface
{
    private readonly MailerInterface $mailer;
    private readonly OptionsRepositoryInterface $options;

    public function __construct(MailerInterface $mailer, OptionsRepositoryInterface $options)
    {
        $this->mailer = $mailer;
        $this->options = $options;
    }

    public function getName(): string
    {
        return 'email';
    }

    public function send(string $level, string $message, array $context = []): bool
    {
        if ($level === 'info') {
            return true;
        }

        $recipient = (string) $this->options->get('admin_email', '');

        if ($recipient === '') {
            return false;
        }

        $siteName = (string) $this->options->get('blogname', 'WordPress');
        $subject = \sprintf('[%s] %s - %s', \strtoupper($level), $siteName, $message);

        $body = $this->formatBody($level, $message, $context);

        return $this->mailer->send($recipient, $subject, $body);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function formatBody(string $level, string $message, array $context): string
    {
        $lines = [];
        $lines[] = 'Alert: ' . $message;
        $lines[] = 'Level: ' . \strtoupper($level);
        $lines[] = 'Time: ' . \gmdate('Y-m-d H:i:s') . ' UTC';
        $lines[] = '';

        if ($context !== []) {
            $lines[] = 'Details:';
            foreach ($context as $key => $value) {
                $display = \is_array($value)
                    ? \implode(', ', \array_map('strval', $value))
                    : (string) $value;
                $lines[] = '  ' . \ucfirst(\str_replace('_', ' ', $key)) . ': ' . $display;
            }
            $lines[] = '';
        }

        $lines[] = '---';
        $lines[] = 'Automated alert from BackTo Framework.';

        return \implode("\n", $lines);
    }
}
