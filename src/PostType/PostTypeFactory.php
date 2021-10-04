<?php

namespace Fantassin\Core\WordPress\PostType;

use Exception;
use Fantassin\Core\WordPress\PostType\Contracts\PostTypeInterface;
use Fantassin\Core\WordPress\PostType\Entity\PostType;

class PostTypeFactory
{
    /**
     * @param string $key
     * @param array $args
     *
     * @return PostType
     * @throws Exception
     */
    public function createPostType(string $key, array $args): PostTypeInterface
    {
        if (empty($key)) {
            throw new Exception(
                'WordPress required post type name. (max. 20 characters, cannot contain capital letters, underscores or spaces)'
            );
        }

        $args = $this->prepareDefaultArgs($args);
        $args = $this->prepareHierarchicalArgs($args);
        $args = $this->prepareEditorArgs($args);

        // Add arbitrary labels if no exists.
        $args = $this->addArgIfNotExist(
            $args,
            'labels',
            [
                'name' => $this->getPluralName($key),
                'singular_name' => $this->getSingularName($key),
            ]
        );

        $postType = new PostType();
        $postType
            ->setKey($key)
            ->setArgs($args);

        return $postType;
    }

    /**
     * Add right supports when post type is hierarchical.
     *
     * @param array $args
     *
     * @return array
     */
    private function prepareHierarchicalArgs(array $args): array
    {
        if (\array_key_exists('hierarchical', $args) && boolval($args['hierarchical']) === true) {
            $supports = ['page-attributes', 'editor', 'title'];
            if (\array_key_exists('supports', $args)) {
                $supports = array_merge($supports, $args['supports']);
            }
            $args['supports'] = $supports;
        }

        return $args;
    }

    /**
     * Add right supports when post type supports editor.
     *
     * @param array $args
     *
     * @return array
     */
    private function prepareEditorArgs(array $args): array
    {
        if (\array_key_exists('supports', $args) && \in_array('editor', $args['supports'])) {
            $supports = ['custom-fields', 'revisions', 'title'];
            if (\array_key_exists('supports', $args)) {
                $supports = array_merge($supports, $args['supports']);
            }
            $args['supports'] = $supports;
        }

        return $args;
    }

    /**
     * @param array $args
     *
     * @return array
     */
    private function prepareDefaultArgs(array $args): array
    {
        $args = $this->addArgIfNotExist($args, 'show_ui', true);
        $args = $this->addArgIfNotExist($args, 'show_in_rest', true);
        $args = $this->addArgIfNotExist($args, 'publicly_queryable', true);

        return $args;
    }

    /**
     * @param array $args
     * @param string $key
     * @param $value
     *
     * @return array
     */
    private function addArgIfNotExist(array $args, string $key, $value): array
    {
        if (!\array_key_exists($key, $args)) {
            $args[$key] = $value;
        }

        return $args;
    }

    public function getSingularName(string $key): string
    {
        $name = str_replace('-', ' ', $key);
        $name = str_replace('_', ' ', $name);
        return ucwords($name);
    }

    public function getPluralName(string $key): string
    {
        return $this->getSingularName($key) . 's';
    }
}
