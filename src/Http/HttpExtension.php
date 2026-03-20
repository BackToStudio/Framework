<?php

declare(strict_types=1);

namespace BackTo\Framework\Http;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Http\Contracts\HttpClientInterface;
use BackTo\Framework\Http\Infrastructure\WordPressHttpClient;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers the HTTP client port binding.
 */
final class HttpExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return null;
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(HttpClientInterface::class, WordPressHttpClient::class);
        $containerBuilder->setAlias(WordPressHttpClient::class, HttpClientInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
