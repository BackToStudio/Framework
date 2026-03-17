<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Disable WooCommerce scripts and styles on non-WooCommerce pages.
 *
 * Saves 200-500 KB of assets on pages that don't need them.
 * Only activates when WooCommerce is installed.
 */
final class OptimizeWooCommerce implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        if (!\class_exists('WooCommerce')) {
            return;
        }

        $this->hookDispatcher->addAction('wp_enqueue_scripts', [$this, 'dequeueWooCommerceAssets'], 99);
    }

    public function dequeueWooCommerceAssets(): void
    {
        if ($this->isWooCommercePage()) {
            return;
        }

        \wp_dequeue_style('woocommerce-general');
        \wp_dequeue_style('woocommerce-layout');
        \wp_dequeue_style('woocommerce-smallscreen');
        \wp_dequeue_style('wc-blocks-style');

        \wp_dequeue_script('wc-cart-fragments');
        \wp_dequeue_script('woocommerce');
        \wp_dequeue_script('wc-add-to-cart');
    }

    private function isWooCommercePage(): bool
    {
        return (function_exists('is_woocommerce') && \is_woocommerce())
            || (function_exists('is_cart') && \is_cart())
            || (function_exists('is_checkout') && \is_checkout())
            || (function_exists('is_account_page') && \is_account_page());
    }
}
