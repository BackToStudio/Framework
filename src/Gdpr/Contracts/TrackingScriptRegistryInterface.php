<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\Contracts;

interface TrackingScriptRegistryInterface
{
    public function add(TrackingScriptInterface $script): self;

    /**
     * @return TrackingScriptInterface[]
     */
    public function getScripts(): array;

    /**
     * @return TrackingScriptInterface[]
     */
    public function getScriptsByCategory(string $categoryKey): array;
}
