<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Hooks\Cleanup;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\ScriptManagerInterface;

/**
 * Disable WooCommerce scripts and styles on non-WooCommerce pages.
 *
 * Saves 200-500 KB of assets on pages that don't need them.
 * Only activates when WooCommerce is installed.
 */
final class OptimizeWooCommerce implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly ScriptManagerInterface $scriptManager;

    public function __construct(HookDispatcherInterface $hookDispatcher, ScriptManagerInterface $scriptManager)
    {
        $this->hookDispatcher = $hookDispatcher;
        $this->scriptManager = $scriptManager;
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

        $this->scriptManager->dequeueStyle('woocommerce-general');
        $this->scriptManager->dequeueStyle('woocommerce-layout');
        $this->scriptManager->dequeueStyle('woocommerce-smallscreen');
        $this->scriptManager->dequeueStyle('wc-blocks-style');

        $this->scriptManager->dequeueScript('wc-cart-fragments');
        $this->scriptManager->dequeueScript('woocommerce');
        $this->scriptManager->dequeueScript('wc-add-to-cart');
    }

    private function isWooCommercePage(): bool
    {
        return (function_exists('is_woocommerce') && \is_woocommerce())
            || (function_exists('is_cart') && \is_cart())
            || (function_exists('is_checkout') && \is_checkout())
            || (function_exists('is_account_page') && \is_account_page());
    }
}
