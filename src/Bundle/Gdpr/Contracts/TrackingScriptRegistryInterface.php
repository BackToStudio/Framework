<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Contracts;

interface TrackingScriptRegistryInterface
{
    public function add(TrackingScriptInterface $script): self;

    
    public function getScripts(): array;

    
    public function getScriptsByCategory(string $categoryKey): array;
}
