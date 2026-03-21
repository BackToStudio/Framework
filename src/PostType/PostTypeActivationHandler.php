<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\PostType\Contracts\PostTypeRegistrarInterface;

/**
 * Handles post type registration during plugin activation.
 *
 * Ensures all custom post types are registered and rewrite rules
 * are flushed when the plugin is activated.
 */
final class PostTypeActivationHandler implements ActivationHooks
{
    private readonly RegisterPostType $registerPostType;
    private readonly PostTypeRegistrarInterface $registrar;

    public function __construct(
        RegisterPostType $registerPostType,
        PostTypeRegistrarInterface $registrar,
    ) {
        $this->registerPostType = $registerPostType;
        $this->registrar = $registrar;
    }

    public function activate(): void
    {
        $this->registerPostType->registerCustomPostTypes();
        $this->registrar->flushRewriteRules();
    }
}
