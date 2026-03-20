<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Port interface for CORS (Cross-Origin Resource Sharing) configuration.
 */
interface CorsManagerInterface
{
    
    public function addAllowedOrigin(string|array $origin): self;

    
    public function addAllowedMethod(string|array $method): self;

    
    public function addAllowedHeader(string|array $header): self;

    public function setAllowCredentials(bool $allow): self;

    public function setMaxAge(int $seconds): self;

    /**
     * @return array<string, string>
     */
    public function buildHeaders(string $requestOrigin): array;
}
