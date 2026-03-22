<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Alert;

use BackTo\Framework\Observability\Contracts\AlertChannelInterface;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Contracts\LoggerInterface;

/**
 * Dispatches alerts to all registered channels.
 *
 * Acts as a fanout: each alert is sent to every registered channel.
 * Channels that fail are logged but do not prevent delivery to other channels.
 */
final class AlertDispatcher implements AlertDispatcherInterface
{
    /** @var AlertChannelInterface[] */
    private array $channels = [];

    private readonly LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function addChannel(AlertChannelInterface $channel): void
    {
        $this->channels[] = $channel;
    }

    public function critical(string $message, array $context = []): void
    {
        $this->dispatch('critical', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->dispatch('warning', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->dispatch('info', $message, $context);
    }

    public function dispatch(string $level, string $message, array $context = []): void
    {
        if ($this->channels === []) {
            $this->logger->warning('Alert dispatched with no channels registered: ' . $message);

            return;
        }

        foreach ($this->channels as $channel) {
            try {
                $channel->send($level, $message, $context);
            } catch (\Throwable $e) {
                $this->logger->error(\sprintf(
                    'Alert channel "%s" failed: %s',
                    $channel->getName(),
                    $e->getMessage(),
                ));
            }
        }
    }

    /**
     * @return AlertChannelInterface[]
     */
    public function getChannels(): array
    {
        return $this->channels;
    }
}
