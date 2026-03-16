<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Contracts;

interface TrackingScriptInterface
{
    public function getHandle(): string;

    public function getCategoryKey(): string;

    public function getSource(): string;

    public function isInline(): bool;

    public function getLocation(): string;

    public function getPriority(): int;
}
