<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;

/**
 * Formats security alert emails (subject line and body).
 */
final class SecurityAlertFormatter
{
    private readonly OptionsRepositoryInterface $options;

    public function __construct(OptionsRepositoryInterface $options)
    {
        $this->options = $options;
    }

    public function buildSubject(string $event, string $severity): string
    {
        $siteName = $this->getSiteName();
        $label = strtoupper($severity);
        $eventLabel = str_replace('_', ' ', $event);

        return sprintf('[%s] %s - %s', $label, $siteName, ucfirst($eventLabel));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function buildBody(string $event, string $severity, array $context): string
    {
        $lines = [];
        $lines[] = 'Security Alert: ' . str_replace('_', ' ', $event);
        $lines[] = 'Severity: ' . strtoupper($severity);
        $lines[] = 'Time: ' . gmdate('Y-m-d H:i:s') . ' UTC';
        $lines[] = '';

        foreach ($context as $key => $value) {
            $displayValue = is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;
            $lines[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $displayValue;
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = 'This is an automated security notification from BackTo Framework.';

        return implode("\n", $lines);
    }

    protected function getSiteName(): string
    {
        return (string) $this->options->get('blogname', 'WordPress');
    }
}
