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
            $args = [
                'object_subtype' => $postMetaStructure->getObjectSubtype(),
                'type' => $postMetaStructure->getType(),
                'label' => $postMetaStructure->getLabel(),
                'description' => $postMetaStructure->getDescription(),
                'single' => $postMetaStructure->isSingle(),
                'show_in_rest' => $postMetaStructure->isShowInRest(),
                'revisions_enabled' => $postMetaStructure->isRevisionsEnabled(),
            ];
    
            if (!is_null($postMetaStructure->getDefault())) {
                $args['default'] = $postMetaStructure->getDefault();
            }
    
            if (is_callable($postMetaStructure->getSanitizeCallback())) {
                $args['sanitize_callback'] = $postMetaStructure->getSanitizeCallback();
            }
    
            if (is_callable($postMetaStructure->getAuthCallback())) {
                $args['auth_callback'] = $postMetaStructure->getAuthCallback();
            }   

            register_post_meta($postMetaStructure->getObjectType(), $postMetaStructure->getMetaKey(), $args);
        }
    }
}