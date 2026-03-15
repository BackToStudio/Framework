<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType;

use BackTo\Framework\Exception\InvalidPostTypeException;
use BackTo\Framework\PostType\Contracts\PostTypeInterface;
use BackTo\Framework\PostType\Entity\PostType;

class PostTypeFactory
{
    /**
     * @param array<string, mixed> $args
     *
     * @throws InvalidPostTypeException
     */
    public function createPostType(string $key, array $args): PostTypeInterface
    {
        if (empty($key)) {
            throw InvalidPostTypeException::emptyKey();
        }

        $args = $this->prepareDefaultArgs($args);
        $args = $this->prepareHierarchicalArgs($args);
        $args = $this->prepareEditorArgs($args);

        // Add arbitrary labels if none exist.
        $args = $this->addArgIfNotExist(
            $args,
            'labels',
            [
                'name' => $key,
                'singular_name' => $key,
            ]
        );

        $postType = new PostType();
        $postType
            ->setKey($key)
            ->setArgs($args);

        return $postType;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function prepareHierarchicalArgs(array $args): array
    {
        if (\array_key_exists('hierarchical', $args) && (bool) $args['hierarchical'] === true) {
            $supports = ['page-attributes', 'editor', 'title'];
            if (\array_key_exists('supports', $args)) {
                $supports = array_merge($supports, $args['supports']);
            }
            $args['supports'] = $supports;
        }

        return $args;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function prepareEditorArgs(array $args): array
    {
        if (\array_key_exists('supports', $args) && \in_array('editor', $args['supports'], true)) {
            $supports = array_merge(['custom-fields', 'revisions', 'title'], $args['supports']);
            $args['supports'] = $supports;
        }

        return $args;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function prepareDefaultArgs(array $args): array
    {
        $args = $this->addArgIfNotExist($args, 'show_ui', true);
        $args = $this->addArgIfNotExist($args, 'show_in_rest', true);
        $args = $this->addArgIfNotExist($args, 'publicly_queryable', true);

        return $args;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function addArgIfNotExist(array $args, string $key, mixed $value): array
    {
        if (!\array_key_exists($key, $args)) {
            $args[$key] = $value;
        }

        return $args;
    }
}
