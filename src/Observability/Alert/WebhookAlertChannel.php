<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Alert;

use BackTo\Framework\Observability\Contracts\AlertChannelInterface;

/**
 * Generic webhook alert channel.
 *
 * Sends alerts as JSON payloads to any HTTP endpoint via POST.
 * Compatible with Slack incoming webhooks, Discord webhooks,
 * and any service that accepts JSON POST requests.
 *
 * The payload format adapts to the target platform based on URL detection:
 * - Slack: uses {text} format
 * - Discord: uses {content} format
 * - Generic: uses {level, message, context, timestamp} format
 */
final class WebhookAlertChannel implements AlertChannelInterface
{
    private readonly string $url;
    private readonly int $timeout;

    public function __construct(string $url, int $timeout = 5)
    {
        $this->url = $url;
        $this->timeout = $timeout;
    }

    public function getName(): string
    {
        return 'webhook';
    }

    public function send(string $level, string $message, array $context = []): bool
    {
        if ($this->url === '') {
            return false;
        }

        $payload = $this->buildPayload($level, $message, $context);

        $response = \wp_remote_post($this->url, [
            'body' => \json_encode($payload, \JSON_THROW_ON_ERROR),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => $this->timeout,
        ]);

        if (\is_wp_error($response)) {
            return false;
        }

        $code = (int) \wp_remote_retrieve_response_code($response);

        return $code >= 200 && $code < 300;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function buildPayload(string $level, string $message, array $context): array
    {
        $formatted = \sprintf('[%s] %s', \strtoupper($level), $message);

        if ($context !== []) {
            $details = [];
            foreach ($context as $key => $value) {
                $display = \is_array($value)
                    ? \implode(', ', \array_map('strval', $value))
                    : (string) $value;
                $details[] = \ucfirst(\str_replace('_', ' ', $key)) . ': ' . $display;
            }
            $formatted .= "\n" . \implode("\n", $details);
        }

        // Slack webhook
        if (\str_contains($this->url, 'hooks.slack.com')) {
            return ['text' => $formatted];
        }

        // Discord webhook
        if (\str_contains($this->url, 'discord.com/api/webhooks')) {
            return ['content' => $formatted];
        }

        // Generic JSON payload
        return [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => \gmdate('Y-m-d\TH:i:s\Z'),
        ];
    }
}
