<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Headers;

/**
 * Validates whether a given origin is allowed by the CORS configuration.
 *
 * Single responsibility: manage the list of allowed origins and check
 * incoming origins against it.
 */
final class CorsOriginValidator
{
    /** @var string[] */
    private array $allowedOrigins = [];

    private bool $allowCredentials = false;

    /**
     * @param string|string[] $origin
     */
    public function addAllowedOrigin(string|array $origin): self
    {
        $origins = is_array($origin) ? $origin : [$origin];

        foreach ($origins as $o) {
            if ($o === '*' && $this->allowCredentials) {
                throw new \InvalidArgumentException(
                    'Cannot add wildcard (*) origin when credentials are enabled. '
                    . 'Specify explicit allowed origins instead.'
                );
            }

            if (!in_array($o, $this->allowedOrigins, true)) {
                $this->allowedOrigins[] = $o;
            }
        }

        return $this;
    }

    public function setAllowCredentials(bool $allow): self
    {
        if ($allow && in_array('*', $this->allowedOrigins, true)) {
            throw new \InvalidArgumentException(
                'Cannot enable credentials with wildcard (*) origin. '
                . 'Specify explicit allowed origins instead.'
            );
        }

        $this->allowCredentials = $allow;

        return $this;
    }

    public function isOriginAllowed(string $origin): bool
    {
        if ($this->allowedOrigins === []) {
            return false;
        }

        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }

        return in_array($origin, $this->allowedOrigins, true);
    }

    /** @return string[] */
    public function getAllowedOrigins(): array
    {
        return $this->allowedOrigins;
    }

    public function isAllowCredentials(): bool
    {
        return $this->allowCredentials;
    }
}
