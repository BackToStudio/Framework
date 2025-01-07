<?php

namespace BackTo\Framework\PostMeta;

use BackTo\Framework\Contracts\Hooks;
use function add_action;
use function register_post_meta;

class RegisterPostMetaStructure implements Hooks {

    private PostMetaStructureRegistry $registry;

    public function __construct(PostMetaStructureRegistry $postMetaStructureRegistry)
    {
        $this->registry = $postMetaStructureRegistry;
    }

    public function hooks()
    {
        add_action('init', [$this, 'registerPostMeta']);
    }

    public function registerPostMeta(): void
    {
        foreach ($this->registry->getPostMetaStructures() as $postMetaStructure) {
            register_post_meta($postMetaStructure->getObjectType(), $postMetaStructure->getMetaKey(), [
                'object_subtype' => $postMetaStructure->getObjectSubtype(),
                'type' => $postMetaStructure->getType(),
                'label' => $postMetaStructure->getLabel(),
                'description' => $postMetaStructure->getDescription(),
                'single' => $postMetaStructure->isSingle(),
                'default' => $postMetaStructure->getDefault(),
                'sanitize_callback' => $postMetaStructure->getSanitizeCallback(),
                'auth_callback' => $postMetaStructure->getAuthCallback(),
                'show_in_rest' => $postMetaStructure->isShowInRest(),
                'revisions_enabled' => $postMetaStructure->isRevisionsEnabled(),
            ]);
        }
    }
}