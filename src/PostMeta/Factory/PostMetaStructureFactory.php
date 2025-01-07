<?php

namespace BackTo\Framework\PostMeta\Factory;

use BackTo\Framework\PostMeta\Entity\PostMetaStructure;
use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;

class PostMetaStructureFactory
{
    public function create(string $key, array $args = []): PostMetaStructureInterface
    {
        $defaultArgs = [
            'type' => 'text',
            'show_in_rest' => true,
            'single' => true,
            'sanitize_callback' => "sanitize_text_field",
        ];
        $args = array_merge($defaultArgs, $args);

        $structure = new PostMetaStructure();
        $structure->setMetaKey($key);
        $structure->setType($args['type']);
        $structure->setDescription($args['description']);
        $structure->setShowInRest($args['show_in_rest']);
        $structure->setSingle($args['single']);
        $structure->setDefault($args['default']);
        $structure->setSanitizeCallback($args['sanitize_callback']);
        $structure->setAuthCallback($args['auth_callback']);

        return $structure;
    }
} 