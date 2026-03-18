<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Entity;

use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;

final class TrackingScript implements TrackingScriptInterface
{
    private const ALLOWED_LOCATIONS = ['head', 'footer'];

    public function __construct(
        private readonly string $handle,
        private readonly string $categoryKey,
        private readonly string $source,
        private bool $inline = false,
        private string $location = 'head',
        private int $priority = 10,
    ) {
        if ($handle === '') {
            throw new \InvalidArgumentException('Tracking script handle cannot be empty.');
        }

        if ($source === '') {
            throw new \InvalidArgumentException('Tracking script source cannot be empty.');
        }

        if (!in_array($location, self::ALLOWED_LOCATIONS, true)) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid location "%s". Allowed: %s.', $location, implode(', ', self::ALLOWED_LOCATIONS))
            );
        }
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function getCategoryKey(): string
    {
        return $this->categoryKey;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
