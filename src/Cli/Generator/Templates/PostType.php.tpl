<?php

declare(strict_types=1);

namespace {{namespace}};

use BackTo\Framework\PostType\Contracts\PostTypeInterface;

class {{className}} implements PostTypeInterface
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
            'supports' => ['title', 'editor', 'thumbnail'],
        ];
    }

    /**
     * @param array<string, mixed> $args
     */
    public function setArgs(array $args): PostTypeInterface
    {
        return $this;
    }
}
