<?php

declare(strict_types=1);

namespace {{namespace}};

use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;

class {{className}} implements TaxonomyInterface
{
    public function getKey(): string
    {
        return '{{key}}';
    }

    /**
     * @return array<string, mixed>
     */
    public function getArgs(): array
    {
        return [
            'label' => '{{label}}',
            'public' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'hierarchical' => true,
        ];
    }

    /**
     * @param array<string, mixed> $args
     */
    public function setArgs(array $args): TaxonomyInterface
    {
        return $this;
    }

    /**
     * @return string[]
     */
    public function getPostTypes(): array
    {
        return [{{postTypes}}];
    }

    /**
     * @param string[] $postTypes
     */
    public function setPostTypes(array $postTypes): TaxonomyInterface
    {
        return $this;
    }
}
