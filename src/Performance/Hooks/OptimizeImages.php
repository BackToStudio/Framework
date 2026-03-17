<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Optimize image loading attributes for better Core Web Vitals.
 *
 * - Adds loading="lazy" to images (WordPress 5.5+ native support)
 * - Adds decoding="async" for non-blocking image decoding
 * - Adds fetchpriority="high" on the first image (likely LCP element)
 * - Skips the first N images to avoid lazy-loading above-the-fold content
 */
final class OptimizeImages implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly int $skipFirst;
    private readonly bool $addDecodingAsync;
    private readonly bool $addFetchPriority;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        int $skipFirst = 1,
        bool $addDecodingAsync = true,
        bool $addFetchPriority = true
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->skipFirst = $skipFirst;
        $this->addDecodingAsync = $addDecodingAsync;
        $this->addFetchPriority = $addFetchPriority;
    }

    public function hooks(): void
    {
        if ($this->addDecodingAsync) {
            $this->hookDispatcher->addFilter('wp_get_attachment_image_attributes', [$this, 'addDecodingAsync']);
        }

        if ($this->addFetchPriority) {
            $this->hookDispatcher->addFilter('wp_content_img_tag', [$this, 'addFetchPriorityToLcp'], 10, 3);
        }

        $this->hookDispatcher->addFilter('wp_lazy_loading_enabled', [$this, 'enableLazyLoading']);
    }

    /**
     * Add decoding="async" to attachment images.
     *
     * @param array<string, string> $attributes
     * @return array<string, string>
     */
    public function addDecodingAsync(array $attributes): array
    {
        if (!isset($attributes['decoding'])) {
            $attributes['decoding'] = 'async';
        }

        return $attributes;
    }

    /**
     * Add fetchpriority="high" to the first image in the content (likely LCP).
     *
     * Uses a static counter to track image position across calls.
     */
    public function addFetchPriorityToLcp(string $imageTag, string $context, int $attachmentId): string
    {
        static $imageCount = 0;
        $imageCount++;

        if ($imageCount <= $this->skipFirst && !str_contains($imageTag, 'fetchpriority')) {
            $imageTag = str_replace('<img ', '<img fetchpriority="high" ', $imageTag);
            $imageTag = str_replace('loading="lazy"', '', $imageTag);
        }

        return $imageTag;
    }

    public function enableLazyLoading(): bool
    {
        return true;
    }
}
